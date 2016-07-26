<?php
declare(strict_types = 1);

namespace ZipStream\File;

use ZipStream\Exception\InvalidArgumentException;

/**
 * Class FileFacts
 * @package ZipStream\File
 */
class FileFacts implements FileFactsInterface
{
    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $crc32Hash;

    /**
     * FileFacts constructor.
     * @param int $length
     * @param int $crc32Hash
     * @throws InvalidArgumentException
     */
    public function __construct(int $length, int $crc32Hash)
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Argument length has to be greater than 0.');
        }

        $this->length = $length;
        $this->crc32Hash = $crc32Hash;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileLength(): int
    {
        return $this->length;
    }

    /**
     * {@inheritDoc}
     */
    public function getCrc32Hash(): int
    {
        return $this->crc32Hash;
    }
}
