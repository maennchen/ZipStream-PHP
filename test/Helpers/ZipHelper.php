<?php
declare(strict_types = 1);

namespace ZipStream\Test\Helpers;

use RuntimeException;
use ZipArchive;

/**
 * Class ZipHelper
 *
 * @package ZipStream\Test\Helpers
 */
trait ZipHelper
{
    /**
     * @return string
     */
    public static function getRandomZipPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('', true) . '.zip';
    }

    /**
     * @param string $zipFileName
     * @return string
     */
    public static function getExtractPathForZip(string $zipFileName): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($zipFileName);
        if (!@mkdir($path) && !is_dir($path)) {
            throw new RuntimeException('Extract Folder can not be created');
        }
        return $path;
    }

    /**
     * @param string $zipFileName
     */
    public static function assertZipIsValid(string $zipFileName)
    {
        try {
            self::ensureZipIsExtracted($zipFileName);
            static::assertTrue(true);
        } catch (RuntimeException $e) {
            static::assertTrue(false, 'Zip could not be extracted.');
        }
    }

    /**
     * @param string $zipFileName
     * @param string $fileName
     * @throws RuntimeException
     */
    public static function assertZipContainsFile(string $zipFileName, string $fileName)
    {
        self::ensureZipIsExtracted($zipFileName);
        self::assertFileExists(self::getExtractPathForZip($zipFileName) . DIRECTORY_SEPARATOR . $fileName);
    }

    /**
     * @param string $zipFileName
     * @param string $fileName
     * @return string
     * @throws RuntimeException
     */
    public static function getZipFileContent(string $zipFileName, string $fileName): string
    {
        self::ensureZipIsExtracted($zipFileName);
        return file_get_contents(self::getExtractPathForZip($zipFileName) . DIRECTORY_SEPARATOR . $fileName);
    }

    /**
     * @param string $zipFileName
     */
    public static function cleanupZip(string $zipFileName)
    {
        @unlink($zipFileName);
        @unlink(self::getExtractPathForZip($zipFileName));
    }

    /**
     * @param string $zipFileName
     * @throws RuntimeException
     */
    private static function ensureZipIsExtracted(string $zipFileName)
    {
        $zipArchive = new ZipArchive();
        if (!$zipArchive->open($zipFileName)) {
            throw new RuntimeException('Zip could not be extracted.');
        }
        $zipArchive->extractTo(self::getExtractPathForZip($zipFileName));
        $zipArchive->close();
    }
}
