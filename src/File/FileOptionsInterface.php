<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * File Options Interface
 *
 * @package ZipStream
 */
interface FileOptionsInterface
{
    /**
     * Last Update Time
     *
     * @return int|null
     */
    public function getTime();

    /**
     * Comment for file
     *
     * @return string|null
     */
    public function getComment();
}
