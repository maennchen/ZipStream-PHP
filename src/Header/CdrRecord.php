<?php
declare(strict_types = 1);

namespace ZipStream\Header;

use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;

/**
 * Class CdrRecord
 * @package ZipStream\Header
 */
class CdrRecord
{
    /**
     * @var FileInterface
     */
    private $file;

    /**
     * @var FileFactsInterface
     */
    private $fileFacts;

    /**
     * @var int
     */
    private $offset;

    /**
     * CdrRecord constructor.
     * @param FileInterface      $file
     * @param FileFactsInterface $fileFacts
     * @param int                $offset
     */
    public function __construct(FileInterface $file, FileFactsInterface $fileFacts, int $offset)
    {
        $this->file = $file;
        $this->fileFacts = $fileFacts;
        $this->offset = $offset;
    }

    /**
     * @return FileInterface
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return FileFactsInterface
     */
    public function getFileFacts()
    {
        return $this->fileFacts;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
