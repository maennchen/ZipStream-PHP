<?php

declare(strict_types=1);

namespace ZipStream\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipStream\ResourceStream;

class ResourceStreamTest extends TestCase
{
    use Util;

    public function testRead(): void
    {
        $resource = fopen('php://memory', 'rw+');

        fwrite($resource, '1234');
        fseek($resource, 0);

        $stream = new ResourceStream($resource);

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
        $resource = fopen('php://memory', 'rw+');

        fwrite($resource, '1234');
        fseek($resource, 0);

        $stream = new ResourceStream($resource);

        $this->assertContains('php://memory', $stream->getMetadata());
        $this->assertSame($stream->getMetadata('uri'), 'php://memory');

        $stream->close();
    }

    public function testNotWritable(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = new ResourceStream(fopen('php://memory', 'r'));
        $this->assertFalse($stream->isWritable());

        $stream->write('7');
    }

    public function testWrite(): void
    {
        $stream = new ResourceStream(fopen('php://memory', 'rw+'));
        $this->assertTrue($stream->isWritable());

        $stream->write('7');

        $stream->seek(0);

        $this->assertSame($stream->read(1), '7');
    }

    public function testUnreadable(): void
    {
        $this->expectException(RuntimeException::class);

        [$tmp] = $this->getTmpFileStream();

        $stream = new ResourceStream(fopen($tmp, 'w'));
        $this->assertFalse($stream->isReadable());

        $stream->read(1);
    }

    public function testUnreqadableGetContent(): void
    {
        $this->expectException(RuntimeException::class);

        [$tmp] = $this->getTmpFileStream();

        $stream = new ResourceStream(fopen($tmp, 'w'));
        $this->assertFalse($stream->isReadable());

        $stream->getContents();
    }

    public function testUnseekable(): void
    {
        if (!file_exists('/dev/null')) {
            $this->markTestSkipped('Needs file /dev/null');
        }

        $this->expectException(RuntimeException::class);

        $stream = new ResourceStream(fopen('/dev/null', 'r'));

        $this->assertFalse($stream->isSeekable());

        $stream->seek(0);
    }
}
