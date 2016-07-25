<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\File;

use PHPUnit_Framework_TestCase;
use ZipStream\File\DeflatedFileFacts;

/**
 * Class DeflatedFileFactsTest
 * @package ZipStream\Test\Unit\File
 */
class DeflatedFileFactsTest extends PHPUnit_Framework_TestCase
{
    public function testValid()
    {
        $facts = new DeflatedFileFacts(42, 7, 7908);

        static::assertEquals(42, $facts->getFileLength());
        static::assertEquals(7, $facts->getDeflatedLength());
        static::assertEquals(7908, $facts->getCrc32Hash());
    }

    /**
     * @expectedException ZipStream\Exception\InvalidArgumentException
     * @expectedExceptionMessage Argument length has to be greater than 0.
     */
    public function testInvalidLength()
    {
        new DeflatedFileFacts(-2546, 809, 7908);
    }

    /**
     * @expectedException ZipStream\Exception\InvalidArgumentException
     * @expectedExceptionMessage Argument deflatedLength has to be greater than 0.
     */
    public function testInvalidDeflatedLength()
    {
        new DeflatedFileFacts(2546, -809, 7908);
    }
}
