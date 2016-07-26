<?php
declare(strict_types = 1);

namespace ZipStream\File;

use ZipStream\Exception\InvalidArgumentException;

/**
 * Class DeflatedFileFacts
 * @package ZipStream\File
 */
class DeflatedFileFacts extends FileFacts implements DeflatedFileFactsInterface
{
    /**
     * @var int
     */
    private $deflatedLength;

    /**
     * FileFacts constructor.
     * @param int $length
     * @param int $deflatedLength
     * @param int $crc32Hash
     * @throws InvalidArgumentException
     */
    public function __construct(int $length, int $deflatedLength, int $crc32Hash)
    {
        if ($deflatedLength <= 0) {
            throw new InvalidArgumentException('Argument deflatedLength has to be greater than 0.');
        }

        parent::__construct($length, $crc32Hash);
        $this->deflatedLength = $deflatedLength;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeflatedLength(): int
    {
        return $this->deflatedLength;
    }
}
