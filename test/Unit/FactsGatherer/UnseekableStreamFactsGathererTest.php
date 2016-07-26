<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\FactsGatherer;

use PHPUnit_Framework_TestCase;
use ZipStream\FactsGatherer\UnseekableStreamFactsGatherer;
use ZipStream\File\SeekableStreamFileInterface;
use ZipStream\File\UnseekableStreamFileInterface;

/**
 * Class UnseekableStreamFactsGathererTest
 * @package ZipStream\Test\Unit\FactsGatherer
 */
class UnseekableStreamFactsGathererTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return UnseekableStreamFactsGatherer
     */
    private static function getInstance(): UnseekableStreamFactsGatherer
    {
        return new UnseekableStreamFactsGatherer();
    }

    public function testSupports()
    {
        $file = $this->createMock(UnseekableStreamFileInterface::class);
        $otherFile = $this->createMock(SeekableStreamFileInterface::class);

        static::assertTrue(self::getInstance()->supports($file));
        static::assertFalse(self::getInstance()->supports($otherFile));
    }

    public function testGatherFacts()
    {
        $file = $this->createMock(UnseekableStreamFileInterface::class);
        $file->method('getLength')
            ->willReturn(42);
        $file->method('getCrc32Hash')
            ->willReturn(12451435);

        $facts = self::getInstance()->gatherFacts($file);

        static::assertEquals(42, $facts->getFileLength());
        static::assertEquals(12451435, $facts->getCrc32Hash());
    }

    public function testGatherDeflatedFacts()
    {
        $file = $this->createMock(UnseekableStreamFileInterface::class);
        $file->method('getLength')
            ->willReturn(42);
        $file->method('getDeflatedLength')
            ->willReturn(7);
        $file->method('getCrc32Hash')
            ->willReturn(12451435);

        $facts = self::getInstance()->gatherDeflatedFacts($file);

        static::assertEquals(42, $facts->getFileLength());
        static::assertEquals(7, $facts->getDeflatedLength());
        static::assertEquals(12451435, $facts->getCrc32Hash());
    }
}
