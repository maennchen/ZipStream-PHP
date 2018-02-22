<?php

namespace ZipStream;

use ZipStream\Exception\FileNotFoundException;
use ZipStream\Exception\FileNotReadableException;
use ZipStream\Exception\IncompatibleOptionsException;
use ZipStream\Exception\EncodingException;

use Psr\Http\Message\StreamInterface;

class File
{
    const HASH_ALGO = 'crc32b';

    const BIT_ZERO_HEADER = 0x0008;
    const BIT_EFS_UTF8 = 0x0800;

    const COMPUTE = 1;
    const SEND = 2;

    public $name;
    public $opt;
    public $meth;
    public $len;
    public $zlen;
    public $crc;
    public $hlen;
    public $ofs;
    public $bits;
    public $version;

    public $zip;

    private $stream;
    private $filter;
    private $deflate;
    private $hash;

    public function __construct(ZipStream $zip, $name, $opt = [], $method = 'deflate')
    {
        $this->zip = $zip;

        $this->name = $name;
        $this->opt = $opt;
        $this->meth = ZipStream::parseMethod(@$opt['method'], $method);
        $this->version = ZipStream::ZIP_VERSION_STORE;
        $this->ofs = new Bigint;
    }

    public function processPath($path)
    {
        if (!is_readable($path)) {
            if (!file_exists($path)) {
                throw new FileNotFoundException($path);
            }
            throw new FileNotReadableException($path);
        }
        if ($this->zip->isLargeFile($path) === false) {
            $data = file_get_contents($path);
            $this->processData($data);
        } else {
            $this->meth = ZipStream::parseMethod(
                @$this->zip->opt[ZipStream::OPTION_LARGE_FILE_METHOD],
                ZipStream::METHOD_STORE
            );
            $stream = new DeflateStream(fopen($path, 'rb'));
            $this->processStream($stream);
        }
    }

    public function processData($data)
    {
        $this->len = new Bigint(strlen($data));
        $this->crc = crc32($data);

        // compress data if needed
        if ($this->meth == ZipStream::METHOD_DEFLATE) {
            $data = gzdeflate($data);
        }

        $this->zlen = new Bigint(strlen($data));
        $this->addFileHeader();
        $this->zip->send($data);
        $this->addFileFooter();
    }

    public function processStream(StreamInterface $stream)
    {
        $this->zlen = new Bigint;
        $this->len = new Bigint;
        $this->stream = $stream;

        if (!function_exists('deflate_init') &&
            $this->meth == ZipStream::METHOD_DEFLATE && !(
                $this->stream instanceof DeflateStream &&
                !$this->zip->opt[ZipStream::OPTION_ZERO_HEADER]
            )) {
            throw new IncompatibleOptionsException(
                'When using PHP version less than 7 deflate method ' .
                'is only available for PHP streams with ' .
                ZipStream::OPTION_ZERO_HEADER .
                ' setting turned off.'
            );
        }

        if ($this->zip->opt[ZipStream::OPTION_ZERO_HEADER]) {
            $this->processStreamWithZeroHeader();
        } else {
            $this->processStreamWithComputedHeader();
        }
    }

    protected function processStreamWithZeroHeader()
    {
        $this->bits |= self::BIT_ZERO_HEADER;
        $this->addFileHeader();
        $this->readStream(self::COMPUTE | self::SEND);
        $this->addFileFooter();
    }

    protected function processStreamWithComputedHeader()
    {
        $this->readStream(self::COMPUTE);
        $this->stream->rewind();

        // incremental compression with deflate_add
        // makes this second read unnecessary
        // but it is only available from PHP 7.0
        if (!$this->deflate && $this->meth == ZipStream::METHOD_DEFLATE) {
            $this->stream->addDeflateFilter(
                @$this->opt['deflate'] ?:
                ZipStream::DEFAULT_DEFLATE_LEVEL
            );
            $this->zlen = new Bigint;
            while (!$this->stream->eof()) {
                $data = $this->stream->read(ZipStream::CHUNKED_READ_BLOCK_SIZE);
                $this->zlen = $this->zlen->add(strlen($data));
            }
            $this->stream->rewind();
        }

        $this->addFileHeader();
        $this->readStream(self::SEND);
        $this->addFileFooter();
    }

    protected function readStream($options = null)
    {
        $this->deflateInit($options);
        while (!$this->stream->eof()) {
            $data = $this->stream->read(ZipStream::CHUNKED_READ_BLOCK_SIZE);
            $this->deflateData($data, $options);
            if ($options & self::SEND) {
                $this->zip->send($data);
            }
        }
        $this->deflateFinish($options);
    }

    protected function deflateInit($options = null)
    {
        $this->hash = hash_init(self::HASH_ALGO);
        if ($this->meth == ZipStream::METHOD_DEFLATE &&
            function_exists('deflate_init')) {
            $this->deflate = deflate_init(
                ZLIB_ENCODING_RAW,
                @$this->opt['deflate'] ?:
                ['level' => ZipStream::DEFAULT_DEFLATE_LEVEL]
            );
        }
    }

    protected function deflateData(&$data, $options = null)
    {
        if ($options & self::COMPUTE) {
            $this->len = $this->len->add(strlen($data));
            hash_update($this->hash, $data);
        }
        if ($this->deflate) {
            $data = deflate_add(
                $this->deflate,
                $data,
                $this->stream->eof()
                    ? ZLIB_FINISH
                    : ZLIB_NO_FLUSH
            );
        }
        if ($options & self::COMPUTE) {
            $this->zlen = $this->zlen->add(strlen($data));
        }
    }

    protected function deflateFinish($options = null)
    {
        if ($options & self::COMPUTE) {
            $this->crc = hexdec(hash_final($this->hash));
        }
    }

    /**
     * Create and send zip header for this file.
     *
     * @param String  $name
     * @param Array   $opt
     * @param Integer $meth
     * @param string  $crc
     * @param Integer $zlen
     * @param Integer $len
     * @return void
     */
    public function addFileHeader()
    {
        $name = $this->filterFilename($this->name);

        // calculate name length
        $nlen = strlen($name);

        // create dos timestamp
        $this->time = @$this->opt['time'] ?: time();
        $time = $this->dostime($this->time);

        if (!mb_check_encoding($name, 'ASCII') ||
            !mb_check_encoding(@$this->opt['comment'], 'ASCII')) {
            // Sets Bit 11: Language encoding flag (EFS).  If this bit is set,
            // the filename and comment fields for this file
            // MUST be encoded using UTF-8. (see APPENDIX D)
            if (!mb_check_encoding($name, 'UTF-8') ||
                !mb_check_encoding(@$this->opt['comment'], 'UTF-8')) {
                throw new EncodingException(
                    'File name and comment should use UTF-8 ' .
                    'if one of them does not fit into ASCII range.'
                );
            }
            $this->bits |= self::BIT_EFS_UTF8;
        }

        if ($this->meth == ZipStream::METHOD_DEFLATE) {
            $this->version = ZipStream::ZIP_VERSION_DEFLATE;
        }

        $force = (boolean) ($this->bits & self::BIT_ZERO_HEADER) &&
            $this->zip->opt[ZipStream::OPTION_ZIP64];

        $footer = $this->buildZip64ExtraBlock($force);

        // If this file will start over 4GB limit in ZIP file,
        // CDR record will have to use Zip64 extension to describe offset
        // to keep consistency we use the same value here
        if ($this->zip->ofs->isOver32()) {
            $this->version = ZipStream::ZIP_VERSION_ZIP64;
        }

        $fields = [
            ['V', ZipStream::FILE_HEADER_SIGNATURE],
            ['v', $this->version],                  // Version needed to Extract
            ['v', $this->bits],                     // General purpose bit flags - data descriptor flag set
            ['v', $this->meth],                     // Compression method
            ['V', $time],                           // Timestamp (DOS Format)
            ['V', $this->crc],                      // CRC32 of data (0 -> moved to data descriptor footer)
            ['V', $this->zlen->getLowFF($force)],   // Length of compressed data (forced to 0xFFFFFFFF for zero header)
            ['V', $this->len->getLowFF($force)],    // Length of original data (forced to 0xFFFFFFFF for zero header)
            ['v', $nlen],                           // Length of filename
            ['v', strlen($footer)],                 // Extra data (see above)
        ];

        // pack fields and calculate "total" length
        $header = ZipStream::packFields($fields);

        // print header and filename
        $data = $header . $name . $footer;
        $this->zip->send($data);

        // save header length
        $this->hlen = strlen($data);
    }

    protected function buildZip64ExtraBlock($force = false)
    {

        $fields = [];
        if ($this->len->isOver32($force)) {
            $fields[] = ['P', $this->len];          // Length of original data
        }

        if ($this->len->isOver32($force)) {
            $fields[] = ['P', $this->zlen];         // Length of compressed data
        }

        if ($this->ofs->isOver32()) {
            $fields[] = ['P', $this->ofs];          // Offset of local header record
        }

        if (!empty($fields)) {
            if (!$this->zip->opt[ZipStream::OPTION_ZIP64]) {
                throw new OverflowException();
            }

            array_unshift(
                $fields,
                ['v', 0x0001],                      // 64 bit extension
                ['v', count($fields)*8]             // Length of data block
            );
            $this->version = ZipStream::ZIP_VERSION_ZIP64;
        }

        return ZipStream::packFields($fields);
    }

    /**
     * Create and send data descriptor footer for this file.
     *
     * @param string  $crc
     * @param Integer $zlen
     * @param Integer $len
     * @return void
     */

    public function addFileFooter()
    {

        if ($this->bits & self::BIT_ZERO_HEADER) {
            if ($this->zip->opt[ZipStream::OPTION_ZIP64]) {
                $fields = [
                    ['V', ZipStream::DATA_DESCRIPTOR_SIGNATURE],
                    ['V', $this->crc],              // CRC32
                    ['P', $this->zlen],             // Length of compressed data
                    ['P', $this->len],              // Length of original data
                ];
            } else {
                $fields = [
                    ['V', ZipStream::DATA_DESCRIPTOR_SIGNATURE],
                    ['V', $this->crc],              // CRC32
                    ['V', $this->zlen],             // Length of compressed data
                    ['V', $this->len],              // Length of original data
                ];
            }
            $footer = ZipStream::packFields($fields);
            $this->zip->send($footer);
        } else {
            $footer = '';
        }
        $this->total_length = Bigint::init($this->hlen)->add($this->zlen)->add(strlen($footer));
        $this->zip->addToCdr($this);
    }

    /**
     * Send CDR record for specified file.
     *
     * @param array $args
     * @return void
     */
    public function addCdrFile()
    {
        $name = $this->filterFilename($this->name);

        // get attributes
        $comment = @$this->opt['comment'];

        // get dos timestamp
        $time = $this->dostime($this->time);

        $footer = $this->buildZip64ExtraBlock();

        $fields = [
            ['V', ZipStream::CDR_FILE_SIGNATURE],   // Central file header signature
            ['v', ZipStream::ZIP_VERSION_MADE_BY],  // Made by version
            ['v', $this->version],                  // Extract by version
            ['v', $this->bits],                     // General purpose bit flags - data descriptor flag set
            ['v', $this->meth],                     // Compression method
            ['V', $time],                           // Timestamp (DOS Format)
            ['V', $this->crc],                      // CRC32
            ['V', $this->zlen->getLowFF()],         // Compressed Data Length
            ['V', $this->len->getLowFF()],          // Original Data Length
            ['v', strlen($name)],                   // Length of filename
            ['v', strlen($footer)],                 // Extra data len (see above)
            ['v', strlen($comment)],                // Length of comment
            ['v', 0],                               // Disk number
            ['v', 0],                               // Internal File Attributes
            ['V', 32],                              // External File Attributes
            ['V', $this->ofs->getLowFF()]           // Relative offset of local header
        ];

        // pack fields, then append name and comment
        $header = ZipStream::packFields($fields);

        $data = $header . $name . $footer . $comment;
        $this->zip->send($data);

        // increment cdr offset
        $this->zip->cdr_ofs = $this->zip->cdr_ofs->add(strlen($data));
    }

    /**
     * Convert a UNIX timestamp to a DOS timestamp.
     *
     * @param Integer $when
     * @return Integer DOS Timestamp
     */
    final protected static function dostime($when)
    {
        // get date array for timestamp
        $d = getdate($when);

        // set lower-bound on dates
        if ($d['year'] < 1980) {
            $d = array(
                'year' => 1980,
                'mon' => 1,
                'mday' => 1,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0
            );
        }

        // remove extra years from 1980
        $d['year'] -= 1980;

        // return date string
        return
            ($d['year']    << 25) |
            ($d['mon']     << 21) |
            ($d['mday']    << 16) |
            ($d['hours']   << 11) |
            ($d['minutes'] <<  5) |
            ($d['seconds'] >>  1);
    }

    /**
     * Strip characters that are not legal in Windows filenames
     * to prevent compatibility issues
     *
     * @param string $filename Unprocessed filename
     * @return string
     */
    public static function filterFilename($filename)
    {
        // strip leading slashes from file name
        // (fixes bug in windows archive viewer)
        $filename = preg_replace('/^\\/+/', '', $filename);

        return str_replace(['\\', ':', '*', '?', '"', '<', '>', '|'], '_', $filename);
    }
}
