<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Interface DeflatedFileFactsInterface
 * @package ZipStream\File
 */
interface DeflatedFileFactsInterface extends FileFactsInterface
{
    public function getDeflatedLength(): int;
}
