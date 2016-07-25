<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Interface MemoryFileInterface
 *
 * @package ZipStream
 */
interface MemoryFileInterface extends FileInterface
{
    /**
     * @return string
     */
    public function getData(): string;
}
