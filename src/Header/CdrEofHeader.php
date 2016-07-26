<?php
declare(strict_types = 1);

namespace ZipStream\Header;

/**
 * Class CdrEofHeader
 * @package ZipStream\Header
 */
class CdrEofHeader
{
    use HeaderHelperTrait;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $headerOffset;

    /**
     * @var int
     */
    private $fileCount;

    /**
     * CdrEofHeader constructor.
     * @param int $offset
     * @param int $headerOffset
     * @param int $fileCount
     */
    public function __construct($offset, $headerOffset, $fileCount)
    {
        $this->offset = $offset;
        $this->headerOffset = $headerOffset;
        $this->fileCount = $fileCount;
    }

    /**
     * Send CDR EOF (Central Directory Record End-of-File) record.
     *
     * @param resource $outputStream
     * @param string   $comment
     */
    public function write($outputStream, string $comment)
    {
        $data = $this->packFields($this->getFields($comment)) . $comment;
        fwrite($outputStream, $data);
    }

    /**
     * @param string $comment
     * @return array
     */
    private function getFields(string $comment): array
    {
        return [ // (from V,F of APPNOTE.TXT)
            ['V', 0x06054b50], // end of central file header signature
            ['v', 0x00], // this disk number
            ['v', 0x00], // number of disk with cdr
            ['v', $this->fileCount], // number of entries in the cdr on this disk
            ['v', $this->fileCount], // number of entries in the cdr
            ['V', $this->headerOffset], // cdr size
            ['V', $this->offset], // cdr ofs
            ['v', strlen($comment)] // zip file comment length
        ];
    }
}
