<?php
declare(strict_types = 1);

namespace ZipStream;

use ZipStream\Exception\UnsupportedFileException;
use ZipStream\File\FileInterface;

/**
 * Interface ArchiveStreamInterface
 *
 * @package ZipStream
 */
interface ArchiveStreamInterface
{
    /**
     * Write file
     *
     * @param FileInterface $file
     * @param bool          $enabledDeflation
     *
     * @throws UnsupportedFileException
     */
    public function writeFile(FileInterface $file, bool $enabledDeflation);

    /**
     * Write EOF File Headers
     * @param string $comment
     * @return
     */
    public function finish(string $comment = '');
}
