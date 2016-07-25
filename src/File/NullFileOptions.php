<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Class NullFileOptions
 * @package ZipStream\File
 */
class NullFileOptions implements FileOptionsInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTime()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getComment()
    {
        return null;
    }
}
