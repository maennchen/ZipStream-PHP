<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\Header;

use PHPUnit_Framework_TestCase;
use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;
use ZipStream\File\FileOptionsInterface;
use ZipStream\File\NullFileOptions;
use ZipStream\Header\CdrHeader;
use ZipStream\Header\CdrRecord;

/**
 * Class CdrHeaderTest
 * @package ZipStream\Test\Unit\Header
 */
class CdrHeaderTest extends PHPUnit_Framework_TestCase
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
        $header = new CdrHeader(new CdrRecord($file, $facts, 7));
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsBAgMGAwYAAAAAAAghRktibgAHAAAABwAAAAgAAAAAAAAAAAAgAAAABwAAAGZpbGUudHh0',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteUmlautName()
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
        $header = new CdrHeader(new CdrRecord($file, $facts, 7));
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsBAgMGAwYACAAAAAghRktibgAHAAAABwAAAA4AAAAAAAAAAAAgAAAABwAAAGZpbGXDpMO2w7wudHh0',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteWithComment()
    {
        $options = $this->createMock(FileOptionsInterface::class);
        $options->method('getComment')
            ->willReturn('Some Comment');
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
        $header = new CdrHeader(new CdrRecord($file, $facts, 7));
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsBAgMGAwYAAAAAAAghRktibgAHAAAABwAAAAgAAAAMAAAAAAAgAAAABwAAAGZpbGUudHh0U29tZSBDb21tZW50',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteWithTime()
    {
        $options = $this->createMock(FileOptionsInterface::class);
        $options->method('getTime')
            ->willReturn(strtotime('-1 month', time()));
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
        $header = new CdrHeader(new CdrRecord($file, $facts, 7));
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsBAgMGAwYAAAAAAAiBRUtibgAHAAAABwAAAAgAAAAAAAAAAAAgAAAABwAAAGZpbGUudHh0',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteWithDeflated()
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getFileName')
            ->willReturn('file.txt');
        $file->method('getOptions')
            ->willReturn(new NullFileOptions());
        $facts = $this->createMock(DeflatedFileFactsInterface::class);
        $facts->method('getDeflatedLength')
            ->willReturn(9);
        $facts->method('getFileLength')
            ->willReturn(7);
        $facts->method('getCrc32Hash')
            ->willReturn(7234123);
        $header = new CdrHeader(new CdrRecord($file, $facts, 7));
        $stream = fopen('php://memory', 'w+');
        $header->write($stream);
        rewind($stream);

        static::assertEquals(
            'UEsBAgMGAwYAAAgAAAghRktibgAJAAAABwAAAAgAAAAAAAAAAAAgAAAABwAAAGZpbGUudHh0',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }
}
