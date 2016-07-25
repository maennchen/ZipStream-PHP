<?php
declare(strict_types = 1);

namespace ZipStream\Header;

use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;

/**
 * Class FileHeader
 *
 * @package ZipStream\Header
 */
class FileHeader
{
    use HeaderHelperTrait;

    /**
     * @var FileInterface
     */
    private $file;

    /**
     * @var FileFactsInterface
     */
    private $fileFacts;

    /**
     * FileHeader constructor.
     * @param FileInterface      $file
     * @param FileFactsInterface $fileFacts
     */
    public function __construct(FileInterface $file, FileFactsInterface $fileFacts)
    {
        $this->file = $file;
        $this->fileFacts = $fileFacts;
    }

    /**
     * @param resource $output
     * @return int
     */
    public function write($output): int
    {
        // pack fields and calculate "total" length
        $packedFields = $this->packFields($this->getFields());
        $deflatedLength = $this->deflationEnabled($this->fileFacts)
            ? $this->fileFacts->getDeflatedLength()
            : $this->fileFacts->getFileLength();
        $cdrLength = strlen($packedFields) + strlen($this->getSanitisedName()) + $deflatedLength;

        // print header and filename
        fwrite($output, $packedFields . $this->getSanitisedName());

        return $cdrLength;
    }

    /**
     * @return array
     */
    private function getFields()
    {
        $deflatedLength = $this->deflationEnabled($this->fileFacts)
            ? $this->fileFacts->getDeflatedLength()
            : $this->fileFacts->getFileLength();

        return [ // (from V.A of APPNOTE.TXT)
            ['V', 0x04034b50],
            // local file header signature

            ['v', 0x000A],
            // version needed to extract
            // FIXED as mentioned in http://linlog.skepticats.com/entries/2012/02/Streaming_ZIP_files_in_PHP.php
            // and http://stackoverflow.com/questions/5573211/
            // dynamically-created-zip-files-by-zipstream-in-php-wont-open-in-osx

            ['v', $this->getGeneralPurposeFlag($this->getSanitisedName())],
            // general purpose bit flag
            ['v', $this->getMeth($this->deflationEnabled($this->fileFacts))],
            // compresion method (deflate or store)
            ['V', $this->dosTime($this->file->getOptions()->getTime())],
            // dos timestamp
            ['V', $this->fileFacts->getCrc32Hash()],
            // crc32 of data
            ['V', $deflatedLength],
            // compressed data length
            ['V', $this->fileFacts->getFileLength()],
            // uncompressed data length
            ['v', strlen($this->getSanitisedName())],
            // filename length
            ['v', 0]
            // extra data len
        ];
    }

    /**
     * @return string
     */
    private function getSanitisedName(): string
    {
        return $this->sanitiseName($this->file->getFileName());
    }
}
