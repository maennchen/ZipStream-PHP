<?php
declare(strict_types = 1);

namespace ZipStream\FactsGatherer;

use ZipStream\File\DeflatedFileFacts;
use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFacts;
use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;
use ZipStream\File\UnseekableStreamFileInterface;

/**
 * Class UnseekableStreamFactsGatherer
 * @package ZipStream\FactsGatherer
 */
class UnseekableStreamFactsGatherer implements DeflatedFactsGathererInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(FileInterface $file): bool
    {
        return $file instanceof UnseekableStreamFileInterface;
    }

    /**
     * {@inheritDoc}
     * @param UnseekableStreamFileInterface $file
     */
    public function gatherFacts(FileInterface $file): FileFactsInterface
    {
        return new FileFacts(
            $file->getLength(),
            $file->getCrc32Hash()
        );
    }

    /**
     * {@inheritDoc}
     * @param UnseekableStreamFileInterface $file
     */
    public function gatherDeflatedFacts(FileInterface $file): DeflatedFileFactsInterface
    {
        return new DeflatedFileFacts(
            $file->getLength(),
            $file->getDeflatedLength(),
            $file->getCrc32Hash()
        );
    }
}
