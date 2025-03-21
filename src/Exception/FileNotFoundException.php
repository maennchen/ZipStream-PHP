<?php

declare(strict_types=1);

namespace ZipStream\Exception;

use ZipStream\Exception;

/**
 * This Exception gets invoked if a file wasn't found
 *
 * @api
 */
class FileNotFoundException extends Exception
{
    /**
     * @internal
     */
    public function __construct(
        public readonly string $path
    ) {
        parent::__construct("The file with the path $path wasn't found.");
    }
}
