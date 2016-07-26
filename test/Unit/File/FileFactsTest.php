<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\File;

use PHPUnit_Framework_TestCase;
use ZipStream\File\FileFacts;

/**
 * Class FileFactsTest
 * @package ZipStream\Test\Unit\File
 */
class FileFactsTest extends PHPUnit_Framework_TestCase
{
    public function testValid()
    {
        $facts = new FileFacts(7, 7908);

        static::assertEquals(7, $facts->getFileLength());
        static::assertEquals(7908, $facts->getCrc32Hash());
    }

    /**
     * @expectedException ZipStream\Exception\InvalidArgumentException
     * @expectedExceptionMessage Argument length has to be greater than 0.
     */
    public function testInvalid()
    {
        new FileFacts(-2546, 7908);
    }
}
