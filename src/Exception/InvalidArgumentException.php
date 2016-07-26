<?php
declare(strict_types = 1);

namespace ZipStream\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;
use ZipStream\Exception;

/**
 * Class InvalidArgumentException
 * @package ZipStream\Exception
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements Exception
{
}
