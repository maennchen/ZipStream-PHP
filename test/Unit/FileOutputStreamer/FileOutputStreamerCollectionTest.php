<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\FileOutputStreamer;

use PHPUnit_Framework_TestCase;
use ZipStream\File\FileInterface;
use ZipStream\FileOutputStreamer\FileOutputStreamerCollection;
use ZipStream\FileOutputStreamer\FileOutputStreamerInterface;

/**
 * Class FileOutputStreamerCollectionTest
 * @package ZipStream\Test\Unit\FileOutputStreamer
 */
class FileOutputStreamerCollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructAndGet()
    {
        $streamer1 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer2 = $this->createMock(FileOutputStreamerInterface::class);

        $collection = new FileOutputStreamerCollection($streamer1, $streamer2);

        static::assertEquals([$streamer1, $streamer2], $collection->get());
    }

    public function testAdd()
    {
        $streamer1 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer2 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer3 = $this->createMock(FileOutputStreamerInterface::class);

        $collection = new FileOutputStreamerCollection($streamer1, $streamer2);
        $collection->add($streamer3);

        static::assertEquals([$streamer1, $streamer2, $streamer3], $collection->get());
    }

    public function testSet()
    {
        $streamer1 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer2 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer3 = $this->createMock(FileOutputStreamerInterface::class);

        $collection = new FileOutputStreamerCollection($streamer1, $streamer2);
        $collection->set($streamer3);

        static::assertEquals([$streamer3], $collection->get());
    }

    public function testClear()
    {
        $streamer1 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer2 = $this->createMock(FileOutputStreamerInterface::class);

        $collection = new FileOutputStreamerCollection($streamer1, $streamer2);
        $collection->clear();

        static::assertEquals([], $collection->get());
    }

    public function testGetFor()
    {
        $streamer1 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer1->method('supports')
            ->willReturn(false);
        $streamer2 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer2->method('supports')
            ->willReturn(true);

        $collection = new FileOutputStreamerCollection($streamer1, $streamer2);

        $file = $this->createMock(FileInterface::class);

        static::assertEquals($streamer2, $collection->getFor($file, false));
    }

    /**
     * @expectedException ZipStream\Exception\UnsupportedFileException
     * @expectedExceptionMessage There is no output streamer registered to stream the file.
     */
    public function testGetForNoGatherers()
    {
        $collection = new FileOutputStreamerCollection();

        $file = $this->createMock(FileInterface::class);

        $collection->getFor($file);
    }

    /**
     * @expectedException ZipStream\Exception\UnsupportedFileException
     * @expectedExceptionMessage There is no output streamer registered to stream the file.
     */
    public function testGetForUnsupportedFile()
    {
        $streamer1 = $this->createMock(FileOutputStreamerInterface::class);
        $streamer1->method('supports')
            ->willReturn(false);

        $collection = new FileOutputStreamerCollection($streamer1);

        $file = $this->createMock(FileInterface::class);

        $collection->getFor($file);
    }
}
