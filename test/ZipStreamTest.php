<?php

namespace ZipStreamTest;

use PHPUnit\Framework\TestCase;
use ZipStream\ZipStream;

/**
 * Test Class for the Main ZipStream CLass
 *
 * @author Jonatan Männchen <jonatan@maennchen.ch>
 * @copyright Copyright (c) 2014, Jonatan Männchen
 */
class ZipStreamTest extends TestCase
{
    const OSX_ARCHIVE_UTILITY = '/System/Library/CoreServices/Applications/Archive Utility.app/Contents/MacOS/Archive Utility';

    /**
     * @expectedException \ZipStream\Exception\InvalidOptionException
     */
    public function testInvalidOptionException()
    {
        // Get ZipStream Object
        $zip = new ZipStream();

        // Set large_file_size very small to be able to test add_large_file method
        $zip->opt['large_file_size'] = 5;

        // Set large_file_method to a wrong value
        $zip->opt['large_file_method'] = 'xy';

        // Trigger error by adding a file
        $zip->addFileFromPath('foobar.php', __FILE__);
    }

    /**
     * @expectedException \ZipStream\Exception\FileNotFoundException
     */
    public function testFileNotFoundException()
    {
        // Get ZipStream Object
        $zip = new ZipStream();

        // Trigger error by adding a file which doesn't exist
        $zip->addFileFromPath('foobar.php', '/foo/bar/foobar.php');
    }

    /**
     * @todo: expectedException ZipStream\Exception\FileNotReadableException
     */
    public function testFileNotReadableException()
    {
        // TODO: How to test this?
        $this->markTestIncomplete('How to test this?');
    }

    public function testDostime()
    {
        $zip = new ZipStream;

        //Allows testing of private method
        $class = new \ReflectionClass ($zip);
        $method = $class->getMethod('dostime');
        $method->setAccessible(true);

        $this->assertSame($method->invoke($zip, 1416246368), 1165069764);

        //January 1 1980 - DOS Epoch.
        $this->assertSame($method->invoke($zip, 315532800), 2162688);

        // January 1 1970 -> January 1 1980 due to minimum DOS Epoch.  @todo Throw Exception?
        $this->assertSame($method->invoke($zip, 0), 2162688);
    }

    public function testAddFile()
    {
        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        $zip->addFile('sample.txt', 'Sample String Data');
        $zip->addFile('test/sample.txt', 'More Simple Sample Data');

        $zip->finish();
        fclose($stream);

        $tmpDir = $this->validateAndExtractZip($tmp);

        $files = $this->getRecursiveFileList($tmpDir);
        $this->assertEquals(array('sample.txt', 'test/sample.txt'), $files);

        $this->assertEquals(file_get_contents($tmpDir . '/sample.txt'), 'Sample String Data');
        $this->assertEquals(file_get_contents($tmpDir . '/test/sample.txt'), 'More Simple Sample Data');
    }

    /**
     * @return array
     */
    protected function getTmpFileStream()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zipstreamtest');
        $stream = fopen($tmp, 'w+');

        return array($tmp, $stream);
    }

    /**
     * @param string $tmp
     * @return string
     */
    protected function validateAndExtractZip($tmp)
    {
        $tmpDir = $this->getTmpDir();

        $zipArch = new \ZipArchive;
        $res = $zipArch->open($tmp);
        if ($res === true) {
            $this->assertEquals(0, $zipArch->status);
            $this->assertEquals(0, $zipArch->statusSys);

            $zipArch->extractTo($tmpDir);
            $zipArch->close();

            return $tmpDir;
        } else {
            $this->fail("Failed to open {$tmp}. Code: $res");

            return $tmpDir;
        }
    }

    /**
     * @return string
     */
    protected function getTmpDir()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zipstreamtest');
        unlink($tmp);
        mkdir($tmp) or $this->fail("Failed to make directory");

        return $tmp;
    }

    /**
     * @param string $path
     * @return string[]
     */
    protected function getRecursiveFileList($path)
    {
        $data = array();
        $path = realpath($path);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

        $pathLen = strlen($path);
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            if (!is_dir($filePath)) {
                $data[] = substr($filePath, $pathLen + 1);
            }
        }

        sort($data);

        return $data;
    }

    public function testAddFileWithStorageMethod()
    {
        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        $zip->addFile('sample.txt', 'Sample String Data', [], ZipStream::METHOD_STORE);
        $zip->addFile('test/sample.txt', 'More Simple Sample Data');
        $zip->finish();
        fclose($stream);

        $zipArch = new \ZipArchive();
        $zipArch->open($tmp);

        $sample1 = $zipArch->statName('sample.txt');
        $sample12 = $zipArch->statName('test/sample.txt');
        $this->assertEquals($sample1['comp_method'], ZipStream::NOCOMPRESS);
        $this->assertEquals($sample12['comp_method'], ZipStream::COMPRESS);

        $zipArch->close();
    }

    public function testDecompressFileWithMacUnarchiver()
    {
        if (!file_exists(self::OSX_ARCHIVE_UTILITY)) {
            $this->markTestSkipped('The Mac OSX Archive Utility is not available.');
        }

        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        $folder = uniqid();

        $zip->addFile($folder . '/sample.txt', 'Sample Data');
        $zip->finish();
        fclose($stream);

        exec(escapeshellarg(self::OSX_ARCHIVE_UTILITY) . ' ' . escapeshellarg($tmp), $output, $returnStatus);

        $this->assertEquals(0, $returnStatus);
        $this->assertCount(0, $output);

        $this->assertFileExists(dirname($tmp) . '/' . $folder . '/sample.txt');
        $this->assertEquals('Sample Data', file_get_contents(dirname($tmp) . '/' . $folder . '/sample.txt'));
    }

    public function testAddFileFromPath()
    {
        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        list($tmpExample, $streamExample) = $this->getTmpFileStream();
        fwrite($streamExample, "Sample String Data");
        fclose($streamExample);
        $zip->addFileFromPath('sample.txt', $tmpExample);

        list($tmpExample, $streamExample) = $this->getTmpFileStream();
        fwrite($streamExample, "More Simple Sample Data");
        fclose($streamExample);
        $zip->addFileFromPath('test/sample.txt', $tmpExample);

        $zip->finish();
        fclose($stream);

        $tmpDir = $this->validateAndExtractZip($tmp);

        $files = $this->getRecursiveFileList($tmpDir);
        $this->assertEquals(array('sample.txt', 'test/sample.txt'), $files);

        $this->assertEquals(file_get_contents($tmpDir . '/sample.txt'), 'Sample String Data');
        $this->assertEquals(file_get_contents($tmpDir . '/test/sample.txt'), 'More Simple Sample Data');
    }

    public function testAddFileFromPathWithStorageMethod()
    {
        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        list($tmpExample, $streamExample) = $this->getTmpFileStream();
        fwrite($streamExample, "Sample String Data");
        fclose($streamExample);
        $zip->addFileFromPath('sample.txt', $tmpExample, [], ZipStream::METHOD_STORE);

        list($tmpExample, $streamExample) = $this->getTmpFileStream();
        fwrite($streamExample, "More Simple Sample Data");
        fclose($streamExample);
        $zip->addFileFromPath('test/sample.txt', $tmpExample);

        $zip->finish();
        fclose($stream);

        $zipArch = new \ZipArchive();
        $zipArch->open($tmp);

        $sample1 = $zipArch->statName('sample.txt');
        $this->assertEquals(ZipStream::NOCOMPRESS, $sample1['comp_method']);

        $sample2 = $zipArch->statName('test/sample.txt');
        $this->assertEquals(ZipStream::COMPRESS, $sample2['comp_method']);

        $zipArch->close();
    }

    public function testAddFileFromPath_largeFileMethods()
    {
        $methods = array(ZipStream::METHOD_STORE, ZipStream::METHOD_DEFLATE);
        foreach ($methods as $method) {
            list($tmp, $stream) = $this->getTmpFileStream();

            $zip = new ZipStream(null, array(
                ZipStream::OPTION_OUTPUT_STREAM => $stream,
                ZipStream::OPTION_LARGE_FILE_METHOD => $method,
                ZipStream::OPTION_LARGE_FILE_SIZE => 5,
            ));

            list($tmpExample, $streamExample) = $this->getTmpFileStream();
            for ($i = 0; $i <= 100000; $i++) {
                fwrite($streamExample, sha1($i));
                if ($i % 100 === 0) {
                    fwrite($streamExample, "\n");
                }
            }
            fclose($streamExample);
            $shaExample = sha1_file($tmpExample);
            $zip->addFileFromPath('sample.txt', $tmpExample);
            unlink($tmpExample);


            $zip->finish();
            fclose($stream);

            $tmpDir = $this->validateAndExtractZip($tmp);

            $files = $this->getRecursiveFileList($tmpDir);
            $this->assertEquals(array('sample.txt'), $files);

            $this->assertEquals(sha1_file($tmpDir . '/sample.txt'), $shaExample, "SHA-1 Mismatch Method: {$method}");
        }
    }

    public function testAddFileFromStream()
    {
        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        $streamExample = fopen('php://temp', 'w+');
        fwrite($streamExample, "Sample String Data");
        rewind($streamExample); // move the pointer back to the beginning of file.
        $zip->addFileFromStream('sample.txt', $streamExample);
        fclose($streamExample);

        $streamExample2 = fopen('php://temp', 'w+');
        fwrite($streamExample2, "More Simple Sample Data");
        rewind($streamExample2); // move the pointer back to the beginning of file.
        $zip->addFileFromStream('test/sample.txt', $streamExample2);
        fclose($streamExample2);

        $zip->finish();
        fclose($stream);

        $tmpDir = $this->validateAndExtractZip($tmp);

        $files = $this->getRecursiveFileList($tmpDir);
        $this->assertEquals(array('sample.txt', 'test/sample.txt'), $files);

        $this->assertEquals(file_get_contents($tmpDir . '/sample.txt'), 'Sample String Data');
        $this->assertEquals(file_get_contents($tmpDir . '/test/sample.txt'), 'More Simple Sample Data');
    }

    public function addFileFromStreamWithStorageMethod()
    {
        list($tmp, $stream) = $this->getTmpFileStream();

        $zip = new ZipStream(null, array(
            ZipStream::OPTION_OUTPUT_STREAM => $stream
        ));

        $streamExample = fopen('php://temp', 'w+');
        fwrite($streamExample, "Sample String Data");
        rewind($streamExample); // move the pointer back to the beginning of file.
        $zip->addFileFromStream('sample.txt', $streamExample, [], ZipStream::METHOD_STORE);
        fclose($streamExample);

        $streamExample2 = fopen('php://temp', 'w+');
        fwrite($streamExample2, "More Simple Sample Data");
        rewind($streamExample2); // move the pointer back to the beginning of file.
        $zip->addFileFromStream('test/sample.txt', $streamExample2, []);
        fclose($streamExample2);

        $zip->finish();
        fclose($stream);

        $zipArch = new \ZipArchive();
        $zipArch->open($tmp);

        $sample1 = $zipArch->statName('sample.txt');
        $this->assertEquals(ZipStream::NOCOMPRESS, $sample1['comp_method']);

        $sample2 = $zipArch->statName('test/sample.txt');
        $this->assertEquals(ZipStream::COMPRESS, $sample2['comp_method']);

        $zipArch->close();
    }
}
