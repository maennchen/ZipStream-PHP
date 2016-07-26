<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\FileOutputStreamer;

use PHPUnit_Framework_TestCase;
use ZipStream\File\MemoryFileInterface;
use ZipStream\File\SeekableStreamFileInterface;
use ZipStream\FileOutputStreamer\MemoryOutputStreamer;

/**
 * Class MemoryOutputStreamerTest
 * @package ZipStream\Test\Unit\FileOutputStreamer
 */
class MemoryOutputStreamerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return MemoryOutputStreamer
     */
    private static function getInstance(): MemoryOutputStreamer
    {
        return new MemoryOutputStreamer();
    }

    public function testSupports()
    {
        $file = $this->createMock(MemoryFileInterface::class);
        $otherFile = $this->createMock(SeekableStreamFileInterface::class);

        static::assertTrue(self::getInstance()->supports($file));
        static::assertFalse(self::getInstance()->supports($otherFile));
    }

    public function testWriteDeflated()
    {
        $data = 'Some Data';
        $file = $this->createMock(MemoryFileInterface::class);
        $file->method('getData')
            ->willReturn($data);

        $stream = fopen('php://memory', 'w+');
        self::getInstance()->write($file, $stream, true);
        rewind($stream);

        static::assertEquals(gzdeflate($data), stream_get_contents($stream));

        fclose($stream);
    }

    public function testWriteUndeflated()
    {
        $data = 'Some Data';
        $file = $this->createMock(MemoryFileInterface::class);
        $file->method('getData')
            ->willReturn($data);

        $stream = fopen('php://memory', 'w+');
        self::getInstance()->write($file, $stream, false);
        rewind($stream);

        static::assertEquals($data, stream_get_contents($stream));

        fclose($stream);
    }
}
