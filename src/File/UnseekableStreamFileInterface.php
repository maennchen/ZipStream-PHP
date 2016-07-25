<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Interface UnseekableStreamFileInterface
 *
 * @package ZipStream
 */
interface UnseekableStreamFileInterface extends StreamableFileInterface
{
    /**
     * @return int
     */
    public function getLength(): int;

    /**
     * @return int
     */
    public function getDeflatedLength(): int;

    /**
     * @return int
     */
    public function getCrc32Hash(): int;
}
