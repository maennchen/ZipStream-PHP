<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Interface FileFactsInterface
 *
 * @package ZipStream\FactsGatherer
 */
interface FileFactsInterface
{
    /**
     * @return int
     */
    public function getCrc32Hash(): int;

    /**
     * @return int
     */
    public function getFileLength(): int;
}
