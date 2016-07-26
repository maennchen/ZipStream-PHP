<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\FactsGatherer;

use PHPUnit_Framework_TestCase;
use ZipStream\FactsGatherer\SeekableStreamFactsGatherer;
use ZipStream\File\SeekableStreamFileInterface;
use ZipStream\File\UnseekableStreamFileInterface;

/**
 * Class SeekableStreamFactsGathererTest
 * @package ZipStream\Test\Unit\FactsGatherer
 */
class SeekableStreamFactsGathererTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return SeekableStreamFactsGatherer
     */
    private static function getInstance(): SeekableStreamFactsGatherer
    {
        return new SeekableStreamFactsGatherer();
    }

    public function testSupports()
    {
        $file = $this->createMock(SeekableStreamFileInterface::class);
        $otherFile = $this->createMock(UnseekableStreamFileInterface::class);

        static::assertTrue(self::getInstance()->supports($file));
        static::assertFalse(self::getInstance()->supports($otherFile));
    }

    public function testGatherFacts()
    {
        $data = 'Some Data';

        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $data);
        rewind($stream);

        $file = $this->createMock(SeekableStreamFileInterface::class);
        $file->method('getStream')
            ->willReturn($stream);

        $facts = self::getInstance()->gatherFacts($file);

        static::assertEquals(strlen($data), $facts->getFileLength());
        static::assertEquals(crc32($data), $facts->getCrc32Hash());

        fclose($stream);
    }

    public function testGatherFactsLargeFile()
    {
        $stream = fopen('php://memory', 'w+');
        for ($i = 0; $i < 1000000; $i++) {
            fwrite($stream, 'Some Data');
        }
        rewind($stream);

        $file = $this->createMock(SeekableStreamFileInterface::class);
        $file->method('getStream')
            ->willReturn($stream);

        $facts = self::getInstance()->gatherFacts($file);

        static::assertEquals(9000000, $facts->getFileLength());
        static::assertEquals(1850059948, $facts->getCrc32Hash());

        fclose($stream);
    }

    public function testGatherDeflatedFacts()
    {
        $data = 'Some Data';

        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $data);
        rewind($stream);

        $file = $this->createMock(SeekableStreamFileInterface::class);
        $file->method('getStream')
            ->willReturn($stream);

        $facts = self::getInstance()->gatherDeflatedFacts($file);

        static::assertEquals(strlen($data), $facts->getFileLength());
        static::assertEquals(strlen(gzdeflate($data)), $facts->getDeflatedLength());
        static::assertEquals(crc32($data), $facts->getCrc32Hash());

        fclose($stream);
    }

    public function testGatherDeflatedFactsLargeFile()
    {
        $stream = fopen('php://memory', 'w+');
        for ($i = 0; $i < 1000000; $i++) {
            fwrite($stream, 'Some Data');
        }
        rewind($stream);

        $file = $this->createMock(SeekableStreamFileInterface::class);
        $file->method('getStream')
            ->willReturn($stream);

        $facts = self::getInstance()->gatherDeflatedFacts($file);

        static::assertEquals(9000000, $facts->getFileLength());
        static::assertEquals(17487, $facts->getDeflatedLength());
        static::assertEquals(1850059948, $facts->getCrc32Hash());

        fclose($stream);
    }
}
