<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\Header;

use PHPUnit_Framework_TestCase;
use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFactsInterface;
use ZipStream\Header\HeaderHelperTrait;

/**
 * Class HeaderHelperTraitTest
 * @package ZipStream\Test\Unit\Header
 */
class HeaderHelperTraitTest extends PHPUnit_Framework_TestCase
{
    use HeaderHelperTrait;

    public function testDosTimeBefore1980()
    {
        $time = strtotime('1974-01-01 01:01:00');

        static::assertEquals(2162688, $this->dosTime($time));
    }

    public function testDosTimeAfter1980()
    {
        $time = strtotime('1994-01-01 01:01:00');

        static::assertEquals(471926816, $this->dosTime($time));
    }

    public function testSanitiseName()
    {
        static::assertEquals('test.txt', $this->sanitiseName('/test.txt'));
        static::assertEquals('test.txt', $this->sanitiseName('test.txt'));
    }

    public function testGetMeth()
    {
        static::assertEquals(0x08, $this->getMeth(true));
        static::assertEquals(0x00, $this->getMeth(false));
    }

    public function testDeflationEnabled()
    {
        static::assertTrue($this->deflationEnabled($this->createMock(DeflatedFileFactsInterface::class)));
        static::assertFalse($this->deflationEnabled($this->createMock(FileFactsInterface::class)));
    }

    public function testGeneralPurposeFlag()
    {
        static::assertEquals(2048, $this->getGeneralPurposeFlag('äöü'));
        static::assertEquals(0, $this->getGeneralPurposeFlag('normal'));
    }

    public function testPackFields()
    {
        static::assertEquals(
            'UEsDBAoA',
            base64_encode(
                $this->packFields(
                    [
                        ['V', 0x04034b50],
                        ['v', 0x000A],
                    ]
                )
            )
        );
    }
}
