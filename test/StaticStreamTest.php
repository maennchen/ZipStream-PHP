<?php

declare(strict_types=1);

namespace ZipStream\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipStream\StaticStream;

class StaticStreamTest extends TestCase
{
    public function testRead(): void
    {
        $content = '1234';

        $stream = new StaticStream($content);

        $this->assertSame($stream->getSize(), 4);

        $this->assertTrue($stream->isReadable());
        $this->assertSame($stream->read(2), '12');
        $this->assertSame($stream->tell(), 2);
        $this->assertSame($stream->read(2), '34');
        $this->assertSame($stream->read(2), '');
        $this->assertTrue($stream->eof());

        $this->assertTrue($stream->isSeekable());
        $stream->seek(2);
        $this->assertSame($stream->getContents(), '34');
        $stream->seek(-2, SEEK_END);
        $this->assertSame($stream->getContents(), '34');
        $stream->seek(-1, SEEK_CUR);
        $this->assertSame($stream->getContents(), '4');

        $this->assertSame((string) $stream, '1234');

        $stream->close();
    }

    public function testMetadata(): void
    {
        $stream = new StaticStream('');

        $this->assertEmpty($stream->getMetadata());
        $this->assertNull($stream->getMetadata('invalid'));

        $stream->close();
    }

    public function testNotWritable(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = new StaticStream('');
        $this->assertFalse($stream->isWritable());

        $stream->write('7');
    }
}
