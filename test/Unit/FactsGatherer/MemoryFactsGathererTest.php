<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\FactsGatherer;

use PHPUnit_Framework_TestCase;
use ZipStream\FactsGatherer\MemoryFactsGatherer;
use ZipStream\File\MemoryFileInterface;
use ZipStream\File\SeekableStreamFileInterface;

/**
 * Class MemoryFactsGatherer
 * @package ZipStream\Test\Unit\FactsGatherer
 */
class MemoryFactsGathererTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return MemoryFactsGatherer
     */
    private static function getInstance(): MemoryFactsGatherer
    {
        return new MemoryFactsGatherer();
    }

    public function testSupports()
    {
        $file = $this->createMock(MemoryFileInterface::class);
        $otherFile = $this->createMock(SeekableStreamFileInterface::class);

        static::assertTrue(self::getInstance()->supports($file));
        static::assertFalse(self::getInstance()->supports($otherFile));
    }

    public function testGatherFacts()
    {
        $data = 'Some Data';
        $file = $this->createMock(MemoryFileInterface::class);
        $file->method('getData')
            ->willReturn($data);

        $facts = self::getInstance()->gatherFacts($file);

        static::assertEquals(strlen($data), $facts->getFileLength());
        static::assertEquals(crc32($data), $facts->getCrc32Hash());
    }

    public function testGatherDeflatedFacts()
    {
        $data = 'Some Data';
        $file = $this->createMock(MemoryFileInterface::class);
        $file->method('getData')
            ->willReturn($data);

        $facts = self::getInstance()->gatherDeflatedFacts($file);

        static::assertEquals(strlen($data), $facts->getFileLength());
        static::assertEquals(strlen(gzdeflate($data)), $facts->getDeflatedLength());
        static::assertEquals(crc32($data), $facts->getCrc32Hash());
    }
}
