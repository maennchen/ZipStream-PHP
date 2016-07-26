<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Interface StreamableFileInterface
 *
 * @package ZipStream
 */
interface StreamableFileInterface extends FileInterface
{
    /**
     * The stream
     *
     * @return resource
     */
    public function getStream();
}
