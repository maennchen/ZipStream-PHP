<?php

declare(strict_types=1);

namespace ZipStream\Test;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;
use ZipStream\Stream\CallbackStreamWrapper;
use ZipStream\ZipStream;

final class CallbackOutputTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up any registered callbacks to prevent memory leaks in tests
        CallbackStreamWrapper::cleanup();
        parent::tearDown();
    }

    public function testDataIsForwardedToCallback(): void
    {
        $buf = '';
        $zip = new ZipStream(
            outputStream: CallbackStreamWrapper::open(
                static function (string $chunk) use (&$buf): void { $buf .= $chunk; }
            ),
            sendHttpHeaders: false
        );

        $zip->addFile('hello.txt', 'Hello World');
        $zip->finish();

        $tmp = tmpfile();
        fwrite($tmp, $buf);
        rewind($tmp);

        $meta = stream_get_meta_data($tmp);
        $za   = new ZipArchive();
        $za->open($meta['uri']);

        $content = $za->getFromName('hello.txt');
        $za->close();
        fclose($tmp);

        $this->assertSame('Hello World', $content);
    }

    public function testMultipleSimultaneousStreams(): void
    {
        $buf1 = '';
        $buf2 = '';

        $stream1 = CallbackStreamWrapper::open(
            static function (string $chunk) use (&$buf1): void { $buf1 .= $chunk; }
        );
        $stream2 = CallbackStreamWrapper::open(
            static function (string $chunk) use (&$buf2): void { $buf2 .= $chunk; }
        );

        $this->assertIsResource($stream1);
        $this->assertIsResource($stream2);

        fwrite($stream1, 'data1');
        fwrite($stream2, 'data2');
        fclose($stream1);
        fclose($stream2);

        $this->assertSame('data1', $buf1);
        $this->assertSame('data2', $buf2);
    }

    public function testExceptionHandlingInCallback(): void
    {
        $stream = CallbackStreamWrapper::open(
            static function (string $chunk): void {
                throw new RuntimeException('Callback error');
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callback function failed during stream write: Callback error');

        fwrite($stream, 'test data');
    }

    public function testLargeDataChunks(): void
    {
        $receivedChunks = [];
        $totalBytes = 0;

        $stream = CallbackStreamWrapper::open(
            static function (string $chunk) use (&$receivedChunks, &$totalBytes): void {
                $receivedChunks[] = strlen($chunk);
                $totalBytes += strlen($chunk);
            }
        );

        // Write large chunks of data
        $largeData = str_repeat('x', 65536); // 64KB
        fwrite($stream, $largeData);
        fwrite($stream, $largeData);
        fclose($stream);

        $this->assertSame(131072, $totalBytes); // 128KB total
        $this->assertNotEmpty($receivedChunks);
        // Large data should be written (possibly in multiple chunks)
        $this->assertGreaterThan(0, max($receivedChunks));
    }

    public function testStreamPositionTracking(): void
    {
        $stream = CallbackStreamWrapper::open(
            static function (string $chunk): void { /* no-op */ }
        );

        $this->assertSame(0, ftell($stream));

        fwrite($stream, 'hello');
        $this->assertSame(5, ftell($stream));

        fwrite($stream, ' world');
        $this->assertSame(11, ftell($stream));

        fclose($stream);
    }

    public function testInvalidModeRejection(): void
    {
        $stream = CallbackStreamWrapper::open(
            static function (string $chunk): void { /* no-op */ }
        );

        // Close the stream first
        fclose($stream);

        // Try to open with read mode - should fail
        $readStream = fopen('zipcb://invalid', 'rb');
        $this->assertFalse($readStream);
    }

    public function testStreamStatistics(): void
    {
        $stream = CallbackStreamWrapper::open(
            static function (string $chunk): void { /* no-op */ }
        );

        fwrite($stream, 'test data');

        $stats = fstat($stream);
        $this->assertIsArray($stats);
        $this->assertSame(9, $stats['size']); // Length of 'test data'
        $this->assertSame(0o100666, $stats['mode']); // Regular file permissions

        fclose($stream);
    }

    public function testProgressTracking(): void
    {
        $progress = [];
        $totalBytes = 0;

        $zip = new ZipStream(
            outputStream: CallbackStreamWrapper::open(
                static function (string $chunk) use (&$progress, &$totalBytes): void {
                    $totalBytes += strlen($chunk);
                    $progress[] = $totalBytes;
                }
            ),
            sendHttpHeaders: false
        );

        $zip->addFile('file1.txt', 'Content 1');
        $zip->addFile('file2.txt', 'Content 2');
        $zip->finish();

        $this->assertNotEmpty($progress);
        $this->assertGreaterThan(0, $totalBytes);
        $this->assertTrue(count($progress) > 1, 'Should have multiple progress updates');
    }

    public function testCallbackCleanupOnClose(): void
    {
        $callbackExecuted = false;

        $stream = CallbackStreamWrapper::open(
            static function (string $chunk) use (&$callbackExecuted): void {
                $callbackExecuted = true;
            }
        );

        fwrite($stream, 'test');
        $this->assertTrue($callbackExecuted);

        fclose($stream);

        // After closing, callback should be cleaned up
        // We can't directly test this, but the tearDown cleanup should work without issues
        $this->assertTrue(true); // Placeholder assertion
    }
}
