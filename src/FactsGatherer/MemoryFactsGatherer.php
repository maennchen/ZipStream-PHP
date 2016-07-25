<?php
declare(strict_types = 1);

namespace ZipStream\FactsGatherer;

use ZipStream\File\DeflatedFileFacts;
use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFacts;
use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;
use ZipStream\File\MemoryFileInterface;

/**
 * Class MemoryFactsGatherer
 * @package ZipStream\FactsGatherer
 */
class MemoryFactsGatherer implements DeflatedFactsGathererInterface
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
    public function gatherFacts(FileInterface $file): FileFactsInterface
    {
        return new FileFacts(
            strlen($file->getData()),
            crc32($file->getData())
        );
    }

    /**
     * {@inheritDoc}
     * @param MemoryFileInterface $file
     */
    public function gatherDeflatedFacts(FileInterface $file): DeflatedFileFactsInterface
    {
        $deflatedData = gzdeflate($file->getData());
        return new DeflatedFileFacts(
            strlen($file->getData()),
            strlen($deflatedData),
            crc32($file->getData())
        );
    }
}
