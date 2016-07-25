<?php
declare(strict_types = 1);

namespace ZipStream\FileOutputStreamer;

use ZipStream\File\FileInterface;

/**
 * Interface FileOutputStreamerInterface
 * @package ZipStream\FileOutputStreamer
 */
interface FileOutputStreamerInterface
{
    /**
     * @param FileInterface $file
     * @return bool
     */
    public function supports(FileInterface $file): bool;

    /**
     * @param FileInterface $file
     * @param resource      $output
     * @param bool          $enabledDeflation
     */
    public function write(FileInterface $file, $output, bool $enabledDeflation);
}
