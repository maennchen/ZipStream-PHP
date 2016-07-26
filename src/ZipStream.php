<?php
namespace ZipStream;
use ZipStream\Exception\InvalidOptionException;
use ZipStream\Exception\FileNotFoundException;
use ZipStream\Exception\FileNotReadableException;

/**
 * ZipStream
 *
 * Streamed, dynamically generated zip archives.
 *
 * @author Paul Duncan <pabs@pablotron.org>
 * @copyright Copyright (C) 2007-2009 Paul Duncan <pabs@pablotron.org>
 *
 * @author Jonatan Männchen <jonatan@maennchen.ch>
 * @copyright Copyright (C) 2014 Jonatan Männchen <jonatan@maennchen.ch>
 *
 * @author Jesse Donat <donatj@gmail.com>
 * @copyright Copyright (C) 2014 Jesse Donat <donatj@gmail.com>
 *
 * @license https://raw.githubusercontent.com/maennchen/ZipStream-PHP/master/LICENCE
 *
 *
 * Requirements:
 *
 * * PHP version 5.1.2 or newer.
 *
 * Usage:
 *
 * Streaming zip archives is a simple, three-step process:
 *
 * 1.  Create the zip stream:
 *
 *     $zip = new ZipStream('example.zip');
 *
 * 2.  Add one or more files to the archive:
 *
 *      * add first file
 *     $data = file_get_contents('some_file.gif');
 *     $zip->addFile('some_file.gif', $data);
 *
 *      * add second file
 *     $data = file_get_contents('some_file.gif');
 *     $zip->addFile('another_file.png', $data);
 *
 * 3.  Finish the zip stream:
 *
 *     $zip->finish();
 *
 * You can also add an archive comment, add comments to individual files,
 * and adjust the timestamp of files.  See the API documentation for each
 * method below for additional information.
 *
 * Example:
 *
 *   // create a new zip stream object
 *   $zip = new ZipStream('some_files.zip');
 *
 *   // list of local files
 *   $files = array('foo.txt', 'bar.jpg');
 *
 *   // read and add each file to the archive
 *   foreach ($files as $path)
 *     $zip->addFile($path, file_get_contents($path));
 *
 *   // write archive footer to stream
 *   $zip->finish();
 */
class ZipStream {
	const VERSION                      = '0.3.0';
	const CRC_ALGORITHM                = 'crc32b';
	const VALUE_DEFERRED_TO_DESCRIPTOR = 0; // value for len, zlen and crc when they are not known until the data descriptor header is sent.
	const BLOCK_SIZE                   = 1048576; // 1 MB. When reading chunks of data on streams.

	// Compression methods
	const METHOD_STORE       = 'store';
	const METHOD_DEFLATE     = 'deflate';
	const HEX_METHOD_STORE   = 0x0000;
	const HEX_METHOD_DEFLATE = 0x0008;

	// General bit flags
	const GENB_FLAG_NONE = 0x0000;
	const GENB_FLAG_ADD  = 0x0008; // File len, zlen, crc are not known until the full file has been streamed.
	const GENB_FLAG_EFS  = 0x0800; // Language encoding flag (EFS).

	// Zip signature headers. @see https://en.wikipedia.org/wiki/Zip_(file_format)#File_headers
	const SIGNATURE_LOCAL_FILE_HEADER               = 0x04034b50;
	const SIGNATURE_DATA_DESCRIPTIOR_HEADER         = 0x08074b50;
	const SIGNATURE_CENTRAL_DIRECTORY_HEADER        = 0x02014b50;
	const SIGNATURE_END_OF_CENTRAL_DIRECTORY_HEADER = 0x06054b50;

	// Static fields values
	const FIELD_VERSION_NEEDED_TO_EXTRACT = 0x000A; // (6 << 8) + 3
	const FIELD_EXTRA_DATA_LEN            = 0;
	const FIELD_DISK_NUMBER_START         = 0;
	const FIELD_INTERNAL_FILE_ATTR        = 0;
	const FIELD_EXTERNAL_FILE_ATTR        = 32;
	const FIELD_THIS_DISK_NUMBER          = 0;
	const FIELD_NUMBER_OF_DISK_WITH_CDR   = 0;

	// Options
	const OPTION_LARGE_FILE_SIZE      = 'large_file_size';
	const OPTION_LARGE_FILE_METHOD    = 'large_file_method';
	const OPTION_SEND_HTTP_HEADERS    = 'send_http_headers';
	const OPTION_HTTP_HEADER_CALLBACK = 'http_header_callback';
	const OPTION_OUTPUT_STREAM        = 'output_stream';
	const OPTION_CONTENT_TYPE         = 'content_type';
	const OPTION_CONTENT_DISPOSITION  = 'content_disposition';

	/**
	 * Global Options
	 *
	 * @var array
	*/
	public $opt = array();

	/**
	 * @var array
	 */
	public $files = array();

	/**
	 * @var integer
	 */
	public $cdr_ofs = 0;

	/**
	 * @var integer
	 */
	public $ofs = 0;

	/**
	 * @var bool
	 */
	protected $need_headers;

	/**
	 * @var null|String
	 */
	protected $output_name;

	/**
	 * Create a new ZipStream object.
	 *
	 * Parameters:
	 *
	 * @param String $name - Name of output file (optional).
	 * @param array $opt  - Hash of archive options (optional, see "Archive Options"
	 *           below).
	 *
	 * Archive Options:
	 *
	 *   comment             - Comment for this archive.
	 *   content_type        - HTTP Content-Type.  Defaults to 'application/x-zip'.
	 *   content_disposition - HTTP Content-Disposition.  Defaults to
	 *                         'attachment; filename=\"FILENAME\"', where
	 *                         FILENAME is the specified filename.
	 *   large_file_size     - Size, in bytes, of the largest file to try
	 *                         and load into memory (used by
	 *                         addFileFromPath()).  Large files may also
	 *                         be compressed differently; see the
	 *                         'large_file_method' option.
	 *   large_file_method   - How to handle large files.  Legal values are
	 *                         'store' (the default), or 'deflate'.  Store
	 *                         sends the file raw and is significantly
	 *                         faster, while 'deflate' compresses the file
	 *                         and is much, much slower.  Note that deflate
	 *                         must compress the file twice and extremely
	 *                         slow.
	 *   sendHttpHeaders   - Boolean indicating whether or not to send
	 *                         the HTTP headers for this file.
	 *
	 * Note that content_type and content_disposition do nothing if you are
	 * not sending HTTP headers.
	 *
	 * Large File Support:
	 *
	 * By default, the method addFileFromPath() will send send files
	 * larger than 20 megabytes along raw rather than attempting to
	 * compress them.  You can change both the maximum size and the
	 * compression behavior using the large_file_* options above, with the
	 * following caveats:
	 *
	 * * For "small" files (e.g. files smaller than large_file_size), the
	 *   memory use can be up to twice that of the actual file.  In other
	 *   words, adding a 10 megabyte file to the archive could potentially
	 *   occupty 20 megabytes of memory.
	 *
	 * * Enabling compression on large files (e.g. files larger than
	 *   large_file_size) is extremely slow, because ZipStream has to pass
	 *   over the large file once to calculate header information, and then
	 *   again to compress and send the actual data.
	 *
	 * Examples:
	 *
	 *   // create a new zip file named 'foo.zip'
	 *   $zip = new ZipStream('foo.zip');
	 *
	 *   // create a new zip file named 'bar.zip' with a comment
	 *   $zip = new ZipStream('bar.zip', array(
	 *     'comment' => 'this is a comment for the zip file.',
	 *   ));
	 *
	 * Notes:
	 *
	 * If you do not set a filename, then this library _DOES NOT_ send HTTP
	 * headers by default.  This behavior is to allow software to send its
	 * own headers (including the filename), and still use this library.
	 */
	public function __construct($name = null, $opt = array()) {

		$defaults = array(
			// set large file defaults: size = 20 megabytes
			self::OPTION_LARGE_FILE_SIZE      => 20 * 1024 * 1024,
			self::OPTION_LARGE_FILE_METHOD    => self::METHOD_STORE,
			self::OPTION_SEND_HTTP_HEADERS    => false,
			self::OPTION_HTTP_HEADER_CALLBACK => 'header',
		);

		// merge and save options
		$this->opt = array_merge($defaults, $opt);

		if (!isset($this->opt[self::OPTION_OUTPUT_STREAM])) {
			$this->opt[self::OPTION_OUTPUT_STREAM] = fopen('php://output', 'w');
		}

		$this->output_name  = $name;
		$this->need_headers = $name || $this->opt[self::OPTION_SEND_HTTP_HEADERS];
	}

	/**
	 * addFile
	 *
	 * add a file to the archive
	 *
	 *  @param String $name - path of file in archive (including directory).
	 *  @param String $data - contents of file
	 *  @param array $opt  - Hash of options for file (optional, see "File Options"
	 *          below).
	 *
	 * File Options:
	 *  time     - Last-modified timestamp (seconds since the epoch) of
	 *             this file.  Defaults to the current time.
	 *  comment  - Comment related to this file.
	 *
	 * Examples:
	 *
	 *   // add a file named 'foo.txt'
	 *   $data = file_get_contents('foo.txt');
	 *   $zip->addFile('foo.txt', $data);
	 *
	 *   // add a file named 'bar.jpg' with a comment and a last-modified
	 *   // time of two hours ago
	 *   $data = file_get_contents('bar.jpg');
	 *   $zip->addFile('bar.jpg', $data, array(
	 *     'time'    => time() - 2 * 3600,
	 *     'comment' => 'this is a comment about bar.jpg',
	 *   ));
	 */
	public function addFile($name, $data, $opt = array()) {
		// compress data
		$zdata = gzdeflate($data);

		// calculate header attributes
		$crc  = crc32($data);
		$zlen = strlen($zdata);
		$len  = strlen($data);
		$meth = self::HEX_METHOD_DEFLATE;

		// send file header
		$num_bytes_written = $this->sendLocalFileHeader($name, $opt, $meth, $crc, $zlen, $len);
		// Total bytes written for this file.
		$num_bytes_written += $zlen;
		$this->addToCdr($name, $opt, $meth, $crc, $zlen, $len, $num_bytes_written);

		// print data
		$this->send($zdata);
	}

	/**
	 * addFileFromPath
	 *
	 * add a file at path to the archive.
	 *
	 * Note that large files may be compresed differently than smaller
	 * files; see the "Large File Support" section above for more
	 * information.
	 *
	 *  @param String $name - name of file in archive (including directory path).
	 *  @param String $path - path to file on disk (note: paths should be encoded using
	 *          UNIX-style forward slashes -- e.g '/path/to/some/file').
	 *  @param array $opt  - Hash of options for file (optional, see "File Options"
	 *          below).
	 *
	 * File Options:
	 *  time     - Last-modified timestamp (seconds since the epoch) of
	 *             this file.  Defaults to the current time.
	 *  comment  - Comment related to this file.
	 *
	 * Examples:
	 *
	 *   // add a file named 'foo.txt' from the local file '/tmp/foo.txt'
	 *   $zip->addFileFromPath('foo.txt', '/tmp/foo.txt');
	 *
	 *   // add a file named 'bigfile.rar' from the local file
	 *   // '/usr/share/bigfile.rar' with a comment and a last-modified
	 *   // time of two hours ago
	 *   $path = '/usr/share/bigfile.rar';
	 *   $zip->addFileFromPath('bigfile.rar', $path, array(
	 *     'time'    => time() - 2 * 3600,
	 *     'comment' => 'this is a comment about bar.jpg',
	 *   ));
	 *
	 * @return void
	 * @throws \ZipStream\Exception\FileNotFoundException
	 * @throws \ZipStream\Exception\FileNotReadableException
	 */
	public function addFileFromPath($name, $path, $opt = array()) {
		if(!is_readable($path)) {
			if(!file_exists($path)) {
				throw new FileNotFoundException($path);
			}
			throw new FileNotReadableException($path);
		}
		if ($this->isLargeFile($path)) {
			// file is too large to be read into memory; add progressively
			$this->addLargeFile($name, $path, $opt);
		} else {
			// file is small enough to read into memory; read file contents and
			// handle with addFile()
			$data = file_get_contents($path);
			$this->addFile($name, $data, $opt);
		}
	}

	/**
	 * addFile_from_stream
	 *
	 * dds an open stream to the archive uncompressed
	 *
	 * NOTE: We only support compression method 'store' atm.
	 * Adding support for deflate should be trivial but would be better to do this at the same time while
	 * deprecating addLargeFile() method in favor of using this method instead for large files.
	 *
	 * @param String $name - path of file in archive (including directory).
	 * @param Resource $stream - contents of file as a stream resource
	 * @param array $opt - Hash of options for file (optional, see "File Options" below).
	 *
	 * File Options:
	 *  time     - Last-modified timestamp (seconds since the epoch) of
	 *             this file.  Defaults to the current time.
	 *  comment  - Comment related to this file.
	 *
	 * Examples:
	 *
	 *   // create a temporary file stream and write text to it
	 *   $fp = tmpfile();
	 *   fwrite($fp, 'The quick brown fox jumped over the lazy dog.');
	 *
	 *   // add a file named 'streamfile.txt' from the content of the stream
	 *   $x->addFile_from_stream('streamfile.txt', $fp);
	 *
	 * @return void
	 */
	public function addFileFromStream($name, $stream, $opt = array()) {
		// Initialize
		$meth     = self::HEX_METHOD_STORE;
		$genb     = self::GENB_FLAG_ADD;
		$zlen     = $len = $crc = self::VALUE_DEFERRED_TO_DESCRIPTOR;
		$hash_ctx = hash_init(self::CRC_ALGORITHM);

		// send local file header.
		$num_bytes_written = $this->sendLocalFileHeader($name, $opt, $meth, $crc, $zlen, $len, $genb);

		//  Read data in chunks and send it to the output as soon as it comes in.
		while (!feof($stream)) {
			// Read and send.
			$data = fread($stream, self::BLOCK_SIZE);
			$this->send($data);

			// Update crc and data lengths.
			hash_update($hash_ctx, $data);
			$len  += strlen($data);
			$zlen += strlen($data);
		}

		// Calculate the actual crc.
		$crc = hexdec(hash_final($hash_ctx));

		// Send the data descriptor right after sending the data content.
		// Now we know the actual content len, zlen and crc.
		$data_descriptor_len = $this->sendDataDescriptorHeader($len, $zlen, $crc);

		// Calculate how many bytes have been written in total.
		$num_bytes_written += $zlen + $data_descriptor_len;

		// add to central directory record and increment offset
		$this->addToCdr($name, $opt, $meth, $crc, $zlen, $len, $num_bytes_written, $genb);
	}

	/**
	 * @param Integer  $len
	 * @param Integer  $zlen
	 * @param String   $crc
	 * @return Integer $num_bytes_written. Num bytes written to zip stream output.
	 */
	protected function sendDataDescriptorHeader($len, $zlen, $crc) {
        $fields = array(
            array(
            	'V',
            	self::SIGNATURE_DATA_DESCRIPTIOR_HEADER
            ), // data descriptor signature. Although optional, some clients will show warnings if not found.
            array(
	            'V',
    	        $crc
            ),  // CRC-32
            array(
            	'V',
            	$zlen
            ), // compressed size
            array(
            	'V',
            	$len
            ), // uncompressed size
        );
        $ret               = $this->packFields($fields);
        $num_bytes_written = strlen($ret);
        $this->send($ret);

        return $num_bytes_written;
    }

	/**
	 * finish
	 *
	 * Write zip footer to stream.
	 *
	 *  Example:
	 *
	 *   // add a list of files to the archive
	 *   $files = array('foo.txt', 'bar.jpg');
	 *   foreach ($files as $path)
	 *     $zip->addFile($path, file_get_contents($path));
	 *
	 *   // write footer to stream
	 *   $zip->finish();
	 *
	 * @return void
	 */
	public function finish() {
		// add trailing cdr record
		$this->sendCdr($this->opt);
		$this->clear();
	}

	/**
	 * Create and send zip header for this file.
	 *
	 * @param String   $name
	 * @param Array    $opt
	 * @param Integer  $meth
	 * @param string   $crc
	 * @param Integer  $zlen
	 * @param Integer  $len
	 * @param Hex      $genb.
	 * @return Integer $num_bytes_written. Num bytes written to zip stream output.
	 */
	protected function sendLocalFileHeader($name, &$opt, $meth, $crc, $zlen, $len, $genb = self::GENB_FLAG_NONE) {
		// strip leading slashes from file name
		// (fixes bug in windows archive viewer)
		$name = preg_replace('/^\\/+/', '', $name);

		// calculate name length
		$nlen = strlen($name);
		// create dos timestamp
 		$opt['time'] = isset($opt['time']) && !empty($opt['time']) ? $opt['time'] : time();
		$dts         = $this->dostime($opt['time']);

		if (mb_check_encoding($name, "UTF-8") && !mb_check_encoding($name, "ASCII"))
		{
			// Sets Bit 11: Language encoding flag (EFS).  If this bit is set,
			// the filename and comment fields for this file
			// MUST be encoded using UTF-8. (see APPENDIX D)
			$genb |= self::GENB_FLAG_EFS;
		}

		// build file header
		$fields = array( // (from V.A of APPNOTE.TXT)
			array(
				'V',
				self::SIGNATURE_LOCAL_FILE_HEADER
			), // local file header signature

			//array('v', (6 << 8) + 3),   // version needed to extract
			array(
				'v',
				self::FIELD_VERSION_NEEDED_TO_EXTRACT
			), // version needed to extract
			//FIXED as mentioned in http://linlog.skepticats.com/entries/2012/02/Streaming_ZIP_files_in_PHP.php
			//and http://stackoverflow.com/questions/5573211/dynamically-created-zip-files-by-zipstream-in-php-wont-open-in-osx

			array(
				'v',
				$genb
			), // general purpose bit flag
			array(
				'v',
				$meth
			), // compresion method (deflate or store)
			array(
				'V',
				$dts
			), // dos timestamp
			array(
				'V',
				$crc
			), // crc32 of data
			array(
				'V',
				$zlen
			), // compressed data length
			array(
				'V',
				$len
			), // uncompressed data length
			array(
				'v',
				$nlen
			), // filename length
			array(
				'v',
				self::FIELD_EXTRA_DATA_LEN
			) // extra data len
		);

		// pack fields and calculate "total" length
		$ret               = $this->packFields($fields);
		$header            = $ret . $name;
		$num_bytes_written = strlen($header);

		// print header and filename
		$this->send($header);

		return $num_bytes_written;
	}

	/**
	 * Add a large file from the given path.
	 *
	 * @param String $name
	 * @param String $path
	 * @param array $opt
	 * @return void
	 * @throws \ZipStream\Exception\InvalidOptionException
	 */
	protected function addLargeFile($name, $path, $opt = array()) {
		$st = stat($path);
		// calculate header attributes
		$zlen = $len = $st['size'];

		$meth_str = $this->opt[self::OPTION_LARGE_FILE_METHOD];
		if ($meth_str == self::METHOD_STORE) {
			// store method
			$meth = self::HEX_METHOD_STORE;
			$crc  = hexdec(hash_file(self::CRC_ALGORITHM, $path));
		} elseif ($meth_str == self::METHOD_DEFLATE) {
			// deflate method
			$meth = self::HEX_METHOD_DEFLATE;

			// open file, calculate crc and compressed file length
			$fh       = fopen($path, 'rb');
			$hash_ctx = hash_init(self::CRC_ALGORITHM);
			$zlen     = 0;

			// read each block, update crc and zlen
			while (!feof($fh)) {
				$data = fread($fh, self::BLOCK_SIZE);
				hash_update($hash_ctx, $data);
			}

			rewind($fh);
			$filter = stream_filter_append($fh, 'zlib.deflate', STREAM_FILTER_READ, 6);

			while (!feof($fh)) {
				$data = fread($fh, self::BLOCK_SIZE);
				$zlen += strlen($data);
			}

			stream_filter_remove($filter);

			// close file and finalize crc
			fclose($fh);

			$crc = hexdec(hash_final($hash_ctx));
		} else {
			throw new InvalidOptionException('large_file_method', array(self::METHOD_STORE, self::METHOD_DEFLATE), $meth_str);
		}

		// send file header
		$num_bytes_written = $this->sendLocalFileHeader($name, $opt, $meth, $crc, $zlen, $len);
		// Even though the data content hasn't been sent already, we know beforehand how many bytes are going to
		// be written ($zlen).
		$num_bytes_written += $zlen;
		$this->addToCdr($name, $opt, $meth, $crc, $zlen, $len, $num_bytes_written);

		// open input file
		$fh = fopen($path, 'rb');

		if ($meth_str == self::METHOD_DEFLATE) {
			$filter = stream_filter_append($fh, 'zlib.deflate', STREAM_FILTER_READ, 6);
		}

		// send file blocks
		while (!feof($fh)) {
			$data = fread($fh, self::BLOCK_SIZE);

			// send data
			$this->send($data);
		}

		if (isset($filter) && is_resource($filter)) {
			stream_filter_remove($filter);
		}

		// close input file
		fclose($fh);
	}

	/**
	 * Is this file larger than large_file_size?
	 *
	 * @param string $path
	 * @return Boolean
	 */
	protected function isLargeFile($path) {
		$st = stat($path);
		return ($this->opt[self::OPTION_LARGE_FILE_SIZE] > 0) && ($st['size'] > $this->opt[self::OPTION_LARGE_FILE_SIZE]);
	}

	/**
	 * Save file attributes for trailing CDR record.
	 *
	 * @param String  $name
	 * @param Array   $opt
	 * @param Integer $meth
	 * @param string  $crc
	 * @param Integer $zlen
	 * @param Integer $len
	 * @param Integer $rec_len
	 * @param Hex     $genb.
	 * @return void
	 * @return void
	 */
	protected function addToCdr($name, $opt, $meth, $crc, $zlen, $len, $rec_len, $genb = self::GENB_FLAG_NONE) {
		$this->files[] = array(
			$name,
			$opt,
			$meth,
			$crc,
			$zlen,
			$len,
			$this->ofs,
			$genb
		);
		$this->ofs += $rec_len;
	}

	/**
	 * Send CDR record for specified file.
	 *
	 * @param array $args
	 * @return void
	 */
	protected function sendCdrFile($args) {
		list($name, $opt, $meth, $crc, $zlen, $len, $ofs, $genb) = $args;

		// get attributes
		$comment = isset($opt['comment']) && !empty($opt['comment']) ? $opt['comment'] : '';

		// get dos timestamp
		$dts = $this->dostime($opt['time']);

		if (mb_check_encoding($name, "UTF-8") && !mb_check_encoding($name, "ASCII"))
		{
			// Sets Bit 11: Language encoding flag (EFS).  If this bit is set,
			// the filename and comment fields for this file
			// MUST be encoded using UTF-8. (see APPENDIX D)
			$genb |= self::GENB_FLAG_EFS;
		}

		$fields = array( // (from V,F of APPNOTE.TXT)
			array(
				'V',
				self::SIGNATURE_CENTRAL_DIRECTORY_HEADER
			), // central file header signature
			array(
				'v',
				self::FIELD_VERSION_NEEDED_TO_EXTRACT
			), // version made by
			array(
				'v',
				self::FIELD_VERSION_NEEDED_TO_EXTRACT
			), // version needed to extract
			array(
				'v',
				$genb
			), // general purpose bit flag
			array(
				'v',
				$meth
			), // compresion method (deflate or store)
			array(
				'V',
				$dts
			), // dos timestamp
			array(
				'V',
				$crc
			), // crc32 of data
			array(
				'V',
				$zlen
			), // compressed data length
			array(
				'V',
				$len
			), // uncompressed data length
			array(
				'v',
				strlen($name)
			), // filename length
			array(
				'v',
				self::FIELD_EXTRA_DATA_LEN
			), // extra data len
			array(
				'v',
				strlen($comment)
			), // file comment length
			array(
				'v',
				self::FIELD_DISK_NUMBER_START
			), // disk number start
			array(
				'v',
				self::FIELD_INTERNAL_FILE_ATTR
			), // internal file attributes
			array(
				'V',
				self::FIELD_EXTERNAL_FILE_ATTR
			), // external file attributes
			array(
				'V',
				$ofs
			) // relative offset of local header
		);

		// pack fields, then append name and comment
		$ret = $this->packFields($fields) . $name . $comment;

		$this->send($ret);

		// increment cdr offset
		$this->cdr_ofs += strlen($ret);
	}

	/**
	 * Send CDR EOF (Central Directory Record End-of-File) record.
	 *
	 * @param array $opt
	 * @return void
	 */
	protected function sendCdrEof($opt = null) {
		$num     = count($this->files);
		$cdr_len = $this->cdr_ofs;
		$cdr_ofs = $this->ofs;

		// grab comment (if specified)
		$comment = '';
		if ($opt && isset($opt['comment'])) {
			$comment = $opt['comment'];
		}

		$fields = array( // (from V,F of APPNOTE.TXT)
			array(
				'V',
				self::SIGNATURE_END_OF_CENTRAL_DIRECTORY_HEADER
			), // end of central file header signature
			array(
				'v',
				self::FIELD_THIS_DISK_NUMBER
			), // this disk number
			array(
				'v',
				self::FIELD_NUMBER_OF_DISK_WITH_CDR
			), // number of disk with cdr
			array(
				'v',
				$num
			), // number of entries in the cdr on this disk
			array(
				'v',
				$num
			), // number of entries in the cdr
			array(
				'V',
				$cdr_len
			), // cdr size
			array(
				'V',
				$cdr_ofs
			), // cdr ofs
			array(
				'v',
				strlen($comment)
			) // zip file comment length
		);

		$ret = $this->packFields($fields) . $comment;
		$this->send($ret);
	}

	/**
	 * Add CDR (Central Directory Record) footer.
	 *
	 * @return void
	 */
	protected function sendCdr($opt = null) {
		foreach ($this->files as $file)
			$this->sendCdrFile($file);
		$this->sendCdrEof($opt);
	}

	/**
	 * Clear all internal variables.  Note that the stream object is not
	 * usable after this.
	 *
	 * @return void
	 */
	protected function clear() {
		$this->files   = array();
		$this->ofs     = 0;
		$this->cdr_ofs = 0;
		$this->opt     = array();
	}

	/**
	 *  Send HTTP headers for this stream.
	 *
	 * @return void
	 */
	protected function sendHttpHeaders() {
		// grab options
		$opt = $this->opt;

		// grab content type from options
		$content_type = 'application/x-zip';
		if (isset($opt[self::OPTION_CONTENT_TYPE])) {
			$content_type = $this->opt[self::OPTION_CONTENT_TYPE];
		}

		// grab content disposition
		$disposition = 'attachment';
		if (isset($opt[self::OPTION_CONTENT_DISPOSITION])) {
			$disposition = $opt[self::OPTION_CONTENT_DISPOSITION];
		}

		if ($this->output_name) {
			$disposition .= "; filename=\"{$this->output_name}\"";
		}

		$headers = array(
			'Content-Type' => $content_type,
			'Content-Disposition' => $disposition,
			'Pragma' => 'public',
			'Cache-Control' => 'public, must-revalidate',
			'Content-Transfer-Encoding' => 'binary'
		);

		$call = $this->opt[self::OPTION_HTTP_HEADER_CALLBACK];
		foreach ($headers as $key => $val)
			$call("$key: $val");
	}

	/**
	 * Send string, sending HTTP headers if necessary.
	 *
	 * @param String $str
	 * @return void
	 */
	protected function send($str) {
		if ($this->need_headers) {
			$this->sendHttpHeaders();
		}
		$this->need_headers = false;

		fwrite($this->opt[self::OPTION_OUTPUT_STREAM], $str);
	}

	/**
	 * Convert a UNIX timestamp to a DOS timestamp.
	 *
	 * @param Integer $when
	 * @return Integer DOS Timestamp
	 */
	protected final function dostime($when) {
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
	 * Create a format string and argument list for pack(), then call
	 * pack() and return the result.
	 *
	 * @param array $fields
	 * @return string
	 */
	protected function packFields($fields) {
		list($fmt, $args) = array(
			'',
			array()
		);

		// populate format string and argument list
		foreach ($fields as $field) {
			$fmt .= $field[0];
			$args[] = $field[1];
		}

		// prepend format string to argument list
		array_unshift($args, $fmt);

		// build output string from header and compressed data
		return call_user_func_array('pack', $args);
	}
}
