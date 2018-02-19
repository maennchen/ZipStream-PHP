<?php

namespace ZipStream;

class File
{
    public $name;
    public $opt;
    public $meth;
    public $len;
    public $zlen;
    public $crc;
    public $hlen;
    public $ofs;

    public $zip;

    public function __construct(ZipStream $zip) {
        $this->zip = $zip;
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
        $this->time = isset($opt['time']) && !empty($opt['time']) ? $opt['time'] : time();
        $time = $this->dostime($this->time);

        // build file header
        if ($this->zip->opt[ZipStream::OPTION_USE_ZIP64])
        {
            $fields = [
                // Header
                ['V', ZipStream::FILE_HEADER_SIGNATURE],
                ['v', ZipStream::ZIP_VERSION_64],       // Version needed to Extract
                ['v', 0b00001000],                      // General purpose bit flags - data descriptor flag set
                ['v', $this->meth],                     // Compression method
                ['V', $time],                           // Timestamp (DOS Format)
                ['V', 0x00000000],                      // CRC32 of data (0 -> moved to data descriptor footer)
                ['V', 0xFFFFFFFF],                      // Length of compressed data (Forced to 0xFFFFFFFF for 64bit extension)
                ['V', 0xFFFFFFFF],                      // Length of original data (Forced to 0xFFFFFFFF for 64bit extension)
                ['v', $nlen],                           // Length of filename
                ['v', 20],                              // Extra data (see below)
            ];

            $fields64 = [
                ['v', 0x0001],                          // 64bit Extension
                ['v', 16],                              // Length of data block
                ['P', 0x0000000000000000],              // Length of original data (0 -> moved to data descriptor footer)
                ['P', 0x0000000000000000],              // Length of compressed data (0 -> moved to data descriptor footer)
            ];
        }
        else
        {
            $fields = [
                // Header
                ['V', ZipStream::FILE_HEADER_SIGNATURE],
                ['v', ZipStream::ZIP_VERSION],          // Version needed to Extract
                ['v', 0b00001000],                      // General purpose bit flags - data descriptor flag set
                ['v', $this->meth],                     // Compression method
                ['V', $time],                           // Timestamp (DOS Format)
                ['V', 0x00000000],                      // CRC32 of data (0 -> moved to data descriptor footer)
                ['V', 0x00000000],                      // Length of compressed data (0 -> moved to data descriptor footer)
                ['V', 0x00000000],                      // Length of original data (0 -> moved to data descriptor footer)
                ['v', $nlen],                           // Length of filename
                ['v', 0],                               // Extra data (0 bytes)
            ];

            $fields64 = [];
        }

        // pack fields and calculate "total" length
        $header = ZipStream::packFields($fields);
        $header64 = ZipStream::packFields($fields64);

        // print header and filename
        $this->zip->send($header . $name . $header64);

        // save header length
        $this->hlen = strlen($header) + $nlen + strlen($header64);
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
        if ($this->zip->opt[ZipStream::OPTION_USE_ZIP64])
        {
            $fields = [
                ['V', ZipStream::DATA_DESCRIPTOR_SIGNATURE],
                ['V', $this->crc],                      // CRC32
                ['P', $this->zlen],                     // Length of compressed data
                ['P', $this->len],                      // Length of original data
            ];
            $flen = 24;
        }
        else
        {
            $fields = [
                ['V', ZipStream::DATA_DESCRIPTOR_SIGNATURE],
                ['V', $this->crc],                      // CRC32
                ['V', $this->zlen],                     // Length of compressed data
                ['V', $this->len],                      // Length of original data
            ];
            $flen = 16;
        }

        $footer = ZipStream::packFields($fields);
        $this->zip->send($footer);
        $this->total_length = Bigint::init($this->hlen)->add($this->zlen)->add($flen);

        // add to central directory record and increment offset
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
        $comment = isset($opt['comment']) && !empty($opt['comment']) ? $opt['comment'] : '';

        // get dos timestamp
        $time = $this->dostime($this->time);

        if ($this->zip->opt[ZipStream::OPTION_USE_ZIP64])
        {
            $fields = [
                ['V', ZipStream::CDR_FILE_SIGNATURE],   // Central file header signature
                ['v', ZipStream::ZIP_VERSION_64],       // Made by version
                ['v', ZipStream::ZIP_VERSION_64],       // Extract by version
                ['v', 0b00001000],                      // General purpose bit flags - data descriptor flag set
                ['v', $this->meth],                     // Compression method
                ['V', $time],                           // Timestamp (DOS Format)
                ['V', $this->crc],                      // CRC32
                ['V', 0xFFFFFFFF],                      // Compressed Data Length (Forced to 0xFFFFFFFF for 64bit Extension)
                ['V', 0xFFFFFFFF],                      // Original Data Length (Forced to 0xFFFFFFFF for 64bit Extension)
                ['v', strlen($name)],                   // Length of filename
                ['v', 28],                              // Extra data len (see below)
                ['v', strlen($comment)],                // Length of comment
                ['v', 0],                               // Disk number
                ['v', 0],                               // Internal File Attributes
                ['V', 32],                              // External File Attributes
                ['V', 0xFFFFFFFF]                       // Relative offset of local header (Forced to 0xFFFFFFFF for 64bit Extension)
            ];

            $fields64 = [
                ['v', 0x0001],                          // 64bit Extension
                ['v', 24],                              // Length of data block
                ['P', $this->len],                      // Length of original data (0 -> moved to data descriptor footer)
                ['P', $this->zlen],                     // Length of compressed data (0 -> moved to data descriptor footer)
                ['P', $this->ofs],                      // Relative Header Offset
            ];
        }
        else
        {
            $fields = [
                ['V', ZipStream::CDR_FILE_SIGNATURE],   // Central file header signature
                ['v', ZipStream::ZIP_VERSION],          // Made by version
                ['v', ZipStream::ZIP_VERSION],          // Extract by version
                ['v', 0b00001000],                      // General purpose bit flags - data descriptor flag set
                ['v', $this->meth],                     // Compression method
                ['V', $time],                           // Timestamp (DOS Format)
                ['V', $this->crc],                      // CRC32
                ['V', $this->zlen],                     // Compressed Data Length
                ['V', $this->len],                      // Original Data Length
                ['v', strlen($name)],                   // Length of filename
                ['v', 0],                               // Extra data len (0bytes)
                ['v', strlen($comment)],                // Length of comment
                ['v', 0],                               // Disk number
                ['v', 0],                               // Internal File Attributes
                ['V', 32],                              // External File Attributes
                ['V', $this->ofs]                       // Relative offset of local header
            ];

            $fields64 = [];
        }

        // pack fields, then append name and comment
        $header = ZipStream::packFields($fields);
        $footer = ZipStream::packFields($fields64);

        $ret = $header . $name . $comment . $footer;

        $this->zip->send($ret);

        // increment cdr offset
        $this->zip->cdr_ofs = $this->zip->cdr_ofs->add(strlen($ret));
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
        return ($d['year'] << 25) | ($d['mon'] << 21) | ($d['mday'] << 16) | ($d['hours'] << 11) | ($d['minutes'] << 5) | ($d['seconds'] >> 1);
    }

    /**
     * Strip characters that are not legal in Windows filenames to prevent compatibility issues
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
