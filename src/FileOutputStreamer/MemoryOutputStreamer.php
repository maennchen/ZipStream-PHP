<?php
declare(strict_types = 1);

namespace ZipStream\FileOutputStreamer;

use ZipStream\File\FileInterface;
use ZipStream\File\MemoryFileInterface;

/**
 * Class MemoryOutputStreamer
 * @package ZipStream\FileOutputStreamer
 */
class MemoryOutputStreamer implements FileOutputStreamerInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(FileInterface $file): bool
    {
        return $file instanceof MemoryFileInterface;
    }

    /**
     * {@inheritDoc}
     * @param MemoryFileInterface $file
     */
    public function write(FileInterface $file, $output, bool $enabledDeflation)
    {
        $data = $file->getData();
        if ($enabledDeflation) {
            $data = gzdeflate($data);
        }
        fwrite($output, $data);
    }
}
