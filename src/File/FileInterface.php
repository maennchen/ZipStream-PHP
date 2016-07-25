<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Interface FileInterface
 *
 * @package ZipStream
 */
interface FileInterface
{
    /**
     * Provide the name of the file
     *
     * @return string
     */
    public function getFileName(): string;

    /**
     * @return FileOptionsInterface
     */
    public function getOptions(): FileOptionsInterface;
}
