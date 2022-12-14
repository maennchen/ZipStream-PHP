<?php

declare(strict_types=1);

namespace ZipStream\Exception;

use ZipStream\Exception;

/**
 * This Exception gets invoked if a resource like `fread` returns false
 */
class ResourceActionException extends Exception
{
    /**
     * @var ?resource
     */
    private $resource;

    /**
     * @param resource $resource
     */
    public function __construct(
        private readonly string $function,
        $resource = null,
    ) {
        $this->resource = $resource;
        parent::__construct('Function ' . $function . 'failed on resource.');
    }
}
