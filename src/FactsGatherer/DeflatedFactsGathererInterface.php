<?php
declare(strict_types = 1);

namespace ZipStream\FactsGatherer;

use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileInterface;

/**
 * Interface DeflatedFactsGathererInterface
 * @package ZipStream\FactsGatherer
 */
interface DeflatedFactsGathererInterface extends FactsGathererInterface
{
    /**
     * @param FileInterface $file
     * @return DeflatedFileFactsInterface
     */
    public function gatherDeflatedFacts(FileInterface $file): DeflatedFileFactsInterface;
}
