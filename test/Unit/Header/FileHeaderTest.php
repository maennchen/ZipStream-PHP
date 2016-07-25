<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\Header;

use PHPUnit_Framework_TestCase;
use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;
use ZipStream\File\FileOptionsInterface;
use ZipStream\File\NullFileOptions;
use ZipStream\Header\FileHeader;

/**
 * Class FileHeaderTest
 * @package ZipStream\Test\Unit\Header
 */
class FileHeaderTest extends PHPUnit_Framework_TestCase
{
    public function testWrite()
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getFileName')
            ->willReturn('file.txt');
        $file->method('getOptions')
            ->willReturn(new NullFileOptions());
        $facts = $this->createMock(FileFactsInterface::class);
        $facts->method('getFileLength')
            ->willReturn(7);
        $facts->method('getCrc32Hash')
            ->willReturn(7234123);
        $header = new FileHeader($file, $facts);
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsDBAoAAAAAAAAIIUZLYm4ABwAAAAcAAAAIAAAAZmlsZS50eHQ=',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteDeflated()
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getFileName')
            ->willReturn('file.txt');
        $file->method('getOptions')
            ->willReturn(new NullFileOptions());
        $facts = $this->createMock(DeflatedFileFactsInterface::class);
        $facts->method('getDeflatedLength')
            ->willReturn(42);
        $facts->method('getFileLength')
            ->willReturn(7);
        $facts->method('getCrc32Hash')
            ->willReturn(7234123);
        $header = new FileHeader($file, $facts);
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsDBAoAAAAIAAAIIUZLYm4AKgAAAAcAAAAIAAAAZmlsZS50eHQ=',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteUmlaut()
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getFileName')
            ->willReturn('fileäöü.txt');
        $file->method('getOptions')
            ->willReturn(new NullFileOptions());
        $facts = $this->createMock(FileFactsInterface::class);
        $facts->method('getFileLength')
            ->willReturn(7);
        $facts->method('getCrc32Hash')
            ->willReturn(7234123);
        $header = new FileHeader($file, $facts);
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsDBAoAAAgAAAAIIUZLYm4ABwAAAAcAAAAOAAAAZmlsZcOkw7bDvC50eHQ=',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteTime()
    {
        $options = $this->createMock(FileOptionsInterface::class);
        $options->method('getTime')
            ->willReturn(strtotime('- 1 mont', time()));

        $file = $this->createMock(FileInterface::class);
        $file->method('getFileName')
            ->willReturn('file.txt');
        $file->method('getOptions')
            ->willReturn($options);
        $facts = $this->createMock(FileFactsInterface::class);
        $facts->method('getFileLength')
            ->willReturn(7);
        $facts->method('getCrc32Hash')
            ->willReturn(7234123);
        $header = new FileHeader($file, $facts);
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsDBAoAAAAAAAA4nUVLYm4ABwAAAAcAAAAIAAAAZmlsZS50eHQ=',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }
}
