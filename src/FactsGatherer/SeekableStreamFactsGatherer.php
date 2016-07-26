<?php
declare(strict_types = 1);

namespace ZipStream\FactsGatherer;

use ZipStream\File\DeflatedFileFacts;
use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFacts;
use ZipStream\File\FileFactsInterface;
use ZipStream\File\FileInterface;
use ZipStream\File\SeekableStreamFileInterface;

/**
 * Class SeekableStreamFactsGatherer
 * @package ZipStream\FactsGatherer
 */
class SeekableStreamFactsGatherer implements DeflatedFactsGathererInterface
{
    /**
     * @var int
     */
    private $blockSize;

    /**
     * @var resource
     */
    private $hashingContext;

    /**
     * SeekableStreamFactsGatherer constructor.
     * @param int $blockSize
     */
    public function __construct(
        int $blockSize = 1048576 // 1 MB
    ) {

        $this->blockSize = $blockSize;
        $this->hashingContext = hash_init('crc32b');
    }

    /**
     * {@inheritDoc}
     */
    public function supports(FileInterface $file): bool
    {
        return $file instanceof SeekableStreamFileInterface;
    }

    /**
     * {@inheritDoc}
     * @param SeekableStreamFileInterface $file
     */
    public function gatherFacts(FileInterface $file): FileFactsInterface
    {
        return new FileFacts(
            $this->calculateFileLength($file),
            $this->calculateFileCrc32Hash($file)
        );
    }

    /**
     * {@inheritDoc}
     * @param SeekableStreamFileInterface $file
     */
    public function gatherDeflatedFacts(FileInterface $file): DeflatedFileFactsInterface
    {
        return new DeflatedFileFacts(
            $this->calculateFileLength($file),
            $this->calculateDeflatedFileLength($file),
            $this->calculateFileCrc32Hash($file)
        );
    }

    /**
     * @param SeekableStreamFileInterface $file
     * @return int
     */
    private function calculateFileLength(SeekableStreamFileInterface $file): int
    {
        fseek($file->getStream(), 0, SEEK_END);
        $length = ftell($file->getStream());
        rewind($file->getStream());

        return $length;
    }

    /**
     * @param SeekableStreamFileInterface $file
     * @return int
     */
    private function calculateDeflatedFileLength(SeekableStreamFileInterface $file): int
    {
        $deflatedLength = 0;
        $filter = stream_filter_append($file->getStream(), 'zlib.deflate', STREAM_FILTER_READ, 6);
        while (!feof($file->getStream())) {
            $data = fread($file->getStream(), $this->blockSize);
            $deflatedLength += strlen($data);
        }
        stream_filter_remove($filter);
        rewind($file->getStream());

        return $deflatedLength;
    }

    /**
     * @param SeekableStreamFileInterface $file
     * @return int
     */
    private function calculateFileCrc32Hash(SeekableStreamFileInterface $file): int
    {
        hash_update_stream($this->hashingContext, $file->getStream());
        $crc32Hash = hexdec(hash_final($this->hashingContext));
        rewind($file->getStream());

        return $crc32Hash;
    }
}
