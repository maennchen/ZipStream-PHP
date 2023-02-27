<?php

declare(strict_types=1);

namespace ZipStream;

use Closure;
use DateTimeInterface;
use DeflateContext;
use RuntimeException;
use ZipStream\Exception\OverflowException;
use ZipStream\Exception\ResourceActionException;
use ZipStream\Exception\StreamNotReadableException;
use ZipStream\Exception\StreamNotSeekableException;

/**
 * @internal
 */
class File
{
    private const CHUNKED_READ_BLOCK_SIZE = 0x1000000;

    private Version $version;

    private int $compressedSize = 0;

    private int $uncompressedSize = 0;

    private int $crc = 0;

    private int $generalPurposeBitFlag = 0;

    private readonly string $fileName;

    private int $totalSize = 0;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param resource $stream
     */
    public function __construct(
        string $fileName,
        private int $startOffset,
        private readonly CompressionMethod $compressionMethod,
        private readonly string $comment,
        private readonly DateTimeInterface $lastModificationDateTime,
        private readonly int $deflateLevel,
        private readonly ?int $maxSize,
        private readonly bool $enableZip64,
        private readonly bool $enableZeroHeader,
        private readonly Closure $send,
        $stream,
    ) {
        $this->fileName = self::filterFilename($fileName);
        $this->checkEncoding();

        if ($this->enableZeroHeader) {
            $this->generalPurposeBitFlag |= GeneralPurposeBitFlag::ZERO_HEADER;
        }

        $this->selectVersion();

        if (!$this->enableZeroHeader && !stream_get_meta_data($stream)['seekable']) {
            throw new StreamNotSeekableException();
        }
        if (!(
            str_contains(stream_get_meta_data($stream)['mode'], 'r')
            || str_contains(stream_get_meta_data($stream)['mode'], 'w+')
            || str_contains(stream_get_meta_data($stream)['mode'], 'a+')
            || str_contains(stream_get_meta_data($stream)['mode'], 'x+')
            || str_contains(stream_get_meta_data($stream)['mode'], 'c+')
        )) {
            throw new StreamNotReadableException();
        }
        $this->stream = $stream;
    }

    public function process(): string
    {
        if (!$this->enableZeroHeader) {
            $this->readStream(send: false);
            if (rewind($this->stream) === false) {
                throw new ResourceActionException('rewind', $this->stream);
            }
        }

        $this->addFileHeader();
        $this->readStream(send: true);
        $this->addFileFooter();

        return $this->getCdrFile();
    }

    /**
     * Create and send zip header for this file.
     */
    private function addFileHeader(): void
    {
        $footer = $this->buildZip64ExtraBlock($this->enableZeroHeader && $this->enableZip64);

        if ($this->generalPurposeBitFlag & GeneralPurposeBitFlag::EFS) {
            // Put the tricky entry to
            // force Linux unzip to lookup EFS flag.
            $footer .= Zs\ExtendedInformationExtraField::generate();
        }


        $data = LocalFileHeader::generate(
            versionNeededToExtract: $this->version->value,
            generalPurposeBitFlag: $this->generalPurposeBitFlag,
            compressionMethod: $this->compressionMethod,
            lastModificationDateTime: $this->lastModificationDateTime,
            crc32UncompressedData: $this->crc,
            compressedSize: ($this->enableZip64 || $this->enableZeroHeader || $this->compressedSize > 0xFFFFFFFF)
                ? 0xFFFFFFFF
                : $this->compressedSize,
            uncompressedSize: ($this->enableZip64 || $this->enableZeroHeader || $this->uncompressedSize > 0xFFFFFFFF)
                ? 0xFFFFFFFF
                : $this->uncompressedSize,
            fileName: $this->fileName,
            extraField: $footer,
        );


        ($this->send)($data);

        $this->totalSize +=  strlen($data);
    }

    /**
     * Strip characters that are not legal in Windows filenames
     * to prevent compatibility issues
     */
    private static function filterFilename(
        /**
         * Unprocessed filename
         */
        string $fileName
    ): string {
        // strip leading slashes from file name
        // (fixes bug in windows archive viewer)
        $fileName = ltrim($fileName, '/');

        return str_replace(['\\', ':', '*', '?', '"', '<', '>', '|'], '_', $fileName);
    }

    private function checkEncoding(): void
    {
        // Sets Bit 11: Language encoding flag (EFS).  If this bit is set,
        // the filename and comment fields for this file
        // MUST be encoded using UTF-8. (see APPENDIX D)
        if (mb_check_encoding($this->fileName, 'UTF-8') &&
                mb_check_encoding($this->comment, 'UTF-8')) {
            $this->generalPurposeBitFlag |= GeneralPurposeBitFlag::EFS;
        }
    }

    private function selectVersion(): void
    {
        if ($this->enableZip64) {
            $this->version = Version::ZIP64;
            return;
        }
        if ($this->compressionMethod === CompressionMethod::DEFLATE) {
            $this->version = Version::DEFLATE;
            return;
        }

        $this->version = Version::STORE;
    }

    private function buildZip64ExtraBlock(bool $force = false): string
    {
        $outputZip64ExtraBlock = false;

        $originalSize = null;
        if ($force || $this->uncompressedSize > 0xFFFFFFFF) {
            $outputZip64ExtraBlock = true;
            $originalSize = $this->uncompressedSize;
        }

        $compressedSize = null;
        if ($force || $this->compressedSize > 0xFFFFFFFF) {
            $outputZip64ExtraBlock = true;
            $compressedSize = $this->compressedSize;
        }

        // If this file will start over 4GB limit in ZIP file,
        // CDR record will have to use Zip64 extension to describe offset
        // to keep consistency we use the same value here
        $relativeHeaderOffset = null;
        if ($this->startOffset > 0xFFFFFFFF) {
            $outputZip64ExtraBlock = true;
            $relativeHeaderOffset = $this->startOffset;
        }

        if (!$outputZip64ExtraBlock) {
            return '';
        }

        if ($this->version !== Version::ZIP64) {
            throw new OverflowException();
        }

        return Zip64\ExtendedInformationExtraField::generate(
            originalSize: $originalSize,
            compressedSize: $compressedSize,
            relativeHeaderOffset: $relativeHeaderOffset,
            diskStartNumber: null,
        );
    }

    private function addFileFooter(): void
    {
        if (($this->compressedSize > 0xFFFFFFFF || $this->uncompressedSize > 0xFFFFFFFF) && $this->version !== Version::ZIP64) {
            throw new OverflowException();
        }

        if (!$this->enableZeroHeader) {
            return;
        }

        if ($this->version === Version::ZIP64) {
            $footer = Zip64\DataDescriptor::generate(
                crc32UncompressedData: $this->crc,
                compressedSize: $this->compressedSize,
                uncompressedSize: $this->uncompressedSize,
            );
        } else {
            $footer = DataDescriptor::generate(
                crc32UncompressedData: $this->crc,
                compressedSize: $this->compressedSize,
                uncompressedSize: $this->uncompressedSize,
            );
        }

        ($this->send)($footer);

        $this->totalSize += strlen($footer);
    }

    private function readStream(bool $send): void
    {
        $this->compressedSize = 0;
        $this->uncompressedSize = 0;
        $hash = hash_init('crc32b');

        $deflate = $this->compressionInit();

        while (!feof($this->stream) && ($this->maxSize === null || $this->uncompressedSize < $this->maxSize)) {
            $readLength = min(($this->maxSize ?? PHP_INT_MAX) - $this->uncompressedSize, self::CHUNKED_READ_BLOCK_SIZE);

            $data = fread($this->stream, $readLength);

            hash_update($hash, $data);

            $this->uncompressedSize += strlen($data);

            if ($deflate) {
                $data =  deflate_add(
                    $deflate,
                    $data,
                    feof($this->stream) ? ZLIB_FINISH : ZLIB_NO_FLUSH
                );
            }

            $this->compressedSize += strlen($data);

            if ($send) {
                ($this->send)($data);
                $this->totalSize += strlen($data);
            }
        }

        $this->crc = hexdec(hash_final($hash));
    }

    private function compressionInit(): ?DeflateContext
    {
        switch($this->compressionMethod) {
            case CompressionMethod::STORE:
                // Noting to do
                return null;
            case CompressionMethod::DEFLATE:
                $deflateContext = deflate_init(
                    ZLIB_ENCODING_RAW,
                    ['level' => $this->deflateLevel]
                );

                if (!$deflateContext) {
                    // @codeCoverageIgnoreStart
                    throw new RuntimeException("Can't initialize deflate context.");
                    // @codeCoverageIgnoreEnd
                }

                // False positive, resource is no longer returned from this function
                return $deflateContext;
            default:
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Unsupported Compression Method ' . print_r($this->compressionMethod, true));
                // @codeCoverageIgnoreEnd
        }
    }

    private function getCdrFile(): string
    {
        $footer = $this->buildZip64ExtraBlock();

        return CentralDirectoryFileHeader::generate(
            versionMadeBy: ZipStream::ZIP_VERSION_MADE_BY,
            versionNeededToExtract:$this->version->value,
            generalPurposeBitFlag: $this->generalPurposeBitFlag,
            compressionMethod: $this->compressionMethod,
            lastModificationDateTime: $this->lastModificationDateTime,
            crc32: $this->crc,
            compressedSize: $this->compressedSize > 0xFFFFFFFF
                ? 0xFFFFFFFF
                : $this->compressedSize,
            uncompressedSize: $this->uncompressedSize > 0xFFFFFFFF
                ? 0xFFFFFFFF
                : $this->uncompressedSize,
            fileName: $this->fileName,
            extraField: $footer,
            fileComment: $this->comment,
            diskNumberStart: 0,
            internalFileAttributes: 0,
            externalFileAttributes: 32,
            relativeOffsetOfLocalHeader: $this->startOffset > 0xFFFFFFFF
                ? 0xFFFFFFFF
                : $this->startOffset,
        );
    }
}
