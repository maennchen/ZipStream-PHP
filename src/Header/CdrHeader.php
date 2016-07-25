<?php
declare(strict_types = 1);

namespace ZipStream\Header;

/**
 * Class CdrHeader
 * @package ZipStream\Header
 */
class CdrHeader
{
    use HeaderHelperTrait;

    /**
     * @var CdrRecord
     */
    private $cdrRecord;

    /**
     * CdrHeader constructor.
     * @param CdrRecord $cdrRecord
     */
    public function __construct(CdrRecord $cdrRecord)
    {
        $this->cdrRecord = $cdrRecord;
    }

    /**
     * Send CDR record for specified file.
     *
     * @param resource $outputStream
     * @return int
     */
    public function write($outputStream): int
    {
        // pack fields, then append name and comment
        $data = $this->packFields($this->getFields()) .
            $this->getSanitisedName() .
            $this->cdrRecord->getFile()->getOptions()->getComment();

        fwrite($outputStream, $data);

        return strlen($data);
    }

    private function getFields()
    {
        $deflatedLength = $this->deflationEnabled($this->cdrRecord->getFileFacts())
            ? $this->cdrRecord->getFileFacts()->getDeflatedLength()
            : $this->cdrRecord->getFileFacts()->getFileLength();

        $comment = $this->cdrRecord->getFile()->getOptions()->getComment() ?? '';

        return [ // (from V,F of APPNOTE.TXT)
            ['V', 0x02014b50],
            // central file header signature
            ['v', (6 << 8) + 3],
            // version made by
            ['v', (6 << 8) + 3],
            // version needed to extract
            ['v', $this->getGeneralPurposeFlag($this->getSanitisedName())],
            // general purpose bit flag
            ['v', $this->getMeth($this->deflationEnabled($this->cdrRecord->getFileFacts()))],
            // compresion method (deflate or store)
            ['V', $this->dosTime($this->cdrRecord->getFile()->getOptions()->getTime())],
            // dos timestamp
            ['V', $this->cdrRecord->getFileFacts()->getCrc32Hash()],
            // crc32 of data
            ['V', $deflatedLength],
            // compressed data length
            ['V', $this->cdrRecord->getFileFacts()->getFileLength()],
            // uncompressed data length
            ['v', strlen($this->getSanitisedName())],
            // filename length
            ['v', 0],
            // extra data len
            ['v', strlen($comment)],
            // file comment length
            ['v', 0],
            // disk number start
            ['v', 0],
            // internal file attributes
            ['V', 32],
            // external file attributes
            ['V', $this->cdrRecord->getOffset()]
            // relative offset of local header
        ];
    }

    /**
     * @return string
     */
    private function getSanitisedName(): string
    {
        return $this->sanitiseName($this->cdrRecord->getFile()->getFileName());
    }
}
