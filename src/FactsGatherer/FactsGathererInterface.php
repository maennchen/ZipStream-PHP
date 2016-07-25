<?php
declare(strict_types = 1);

namespace ZipStream\FactsGatherer;

use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;

/**
 * Interface FactsGathererInterface
 *
 * @package ZipStream\FactsGatherer
 */
interface FactsGathererInterface
{
    /**
     * @param FileInterface $file
     * @return bool
     */
    public function supports(FileInterface $file): bool;

    /**
     * @param FileInterface $file
     * @return FileFactsInterface
     */
    public function gatherFacts(FileInterface $file): FileFactsInterface;
}
