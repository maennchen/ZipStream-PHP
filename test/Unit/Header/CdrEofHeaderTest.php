<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\Header;

use PHPUnit_Framework_TestCase;
use ZipStream\Header\CdrEofHeader;

/**
 * Class CdrEofHeaderTest
 * @package ZipStream\Test\Unit\Header
 */
class CdrEofHeaderTest extends PHPUnit_Framework_TestCase
{
    public function testWriteWithComment()
    {
        $header = new CdrEofHeader(7, 42, 3);
        $stream = fopen('php://memory', 'w+');
        $header->write($stream, 'Some Comment');
        rewind($stream);

        static::assertEquals(
            'UEsFBgAAAAADAAMAKgAAAAcAAAAMAFNvbWUgQ29tbWVudA==',
            base64_encode(stream_get_contents($stream))
        );

        fclose($stream);
    }

    public function testWriteWithoutComment()
    {
        $header = new CdrEofHeader(7, 42, 3);
        $stream = fopen('php://memory', 'w+');
        $header->write($stream, '');
        rewind($stream);

        static::assertEquals('UEsFBgAAAAADAAMAKgAAAAcAAAAAAA==', base64_encode(stream_get_contents($stream)));

        fclose($stream);
    }
}
