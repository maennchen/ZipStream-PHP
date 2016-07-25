<?php
declare(strict_types = 1);

namespace ZipStream\Test\Functional;

use PHPUnit_Framework_TestCase;
use ZipStream\ArchiveStreamInterface;
use ZipStream\FactsGatherer\FactsGathererCollection;
use ZipStream\FactsGatherer\MemoryFactsGatherer;
use ZipStream\FactsGatherer\SeekableStreamFactsGatherer;
use ZipStream\FactsGatherer\UnseekableStreamFactsGatherer;
use ZipStream\File\MemoryFile;
use ZipStream\FileOutputStreamer\FileOutputStreamerCollection;
use ZipStream\FileOutputStreamer\MemoryOutputStreamer;
use ZipStream\Test\Helpers\ZipHelper;
use ZipStream\ZipStream;

/**
 * Class ZipStreamTest
 * @package ZipStream\Test\Functional
 */
class ZipStreamTest extends PHPUnit_Framework_TestCase
{
    use ZipHelper;

    /**
     * @param resource $output
     * @return ArchiveStreamInterface
     */
    private static function getArchiveStream($output): ArchiveStreamInterface
    {
        return new ZipStream(
            new FactsGathererCollection(
                new MemoryFactsGatherer(),
                new SeekableStreamFactsGatherer(),
                new UnseekableStreamFactsGatherer()
            ),
            new FileOutputStreamerCollection(
                new MemoryOutputStreamer()
            ),
            $output
        );
    }

    public function testTwoMemoryFilesDeflated()
    {
        $fileName = self::getRandomZipPath();

        $stream = fopen($fileName, 'w+');

        $file1 = new MemoryFile('test1.txt', 'Test');
        $file2 = new MemoryFile('test2.txt', 'Test');
        $archiveStream = self::getArchiveStream($stream);
        $archiveStream->writeFile($file1, true);
        $archiveStream->writeFile($file2, true);
        $archiveStream->finish();

        static::assertZipIsValid($fileName);
        static::assertZipContainsFile($fileName, 'test1.txt');
        static::assertEquals('Test', static::getZipFileContent($fileName, 'test1.txt'));
        static::assertZipContainsFile($fileName, 'test2.txt');
        static::assertEquals('Test', static::getZipFileContent($fileName, 'test2.txt'));

        fclose($stream);
        static::cleanupZip($fileName);
    }

    public function testTwoMemoryFilesUndeflated()
    {
        $fileName = self::getRandomZipPath();

        $stream = fopen($fileName, 'w+');

        $file1 = new MemoryFile('test1.txt', 'Test');
        $file2 = new MemoryFile('test2.txt', 'Test');
        $archiveStream = self::getArchiveStream($stream);
        $archiveStream->writeFile($file1, false);
        $archiveStream->writeFile($file2, false);
        $archiveStream->finish();

        static::assertZipIsValid($fileName);
        static::assertZipContainsFile($fileName, 'test1.txt');
        static::assertEquals('Test', static::getZipFileContent($fileName, 'test1.txt'));
        static::assertZipContainsFile($fileName, 'test2.txt');
        static::assertEquals('Test', static::getZipFileContent($fileName, 'test2.txt'));

        fclose($stream);
        static::cleanupZip($fileName);
    }
}
