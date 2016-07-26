<?php
declare(strict_types = 1);

namespace ZipStream;

use ZipStream\FactsGatherer\DeflatedFactsGathererInterface;
use ZipStream\FactsGatherer\FactsGathererCollection;
use ZipStream\FactsGatherer\FactsGathererInterface;
use ZipStream\File\FileInterface;
use ZipStream\FileOutputStreamer\FileOutputStreamerCollection;
use ZipStream\Header\CdrEofHeader;
use ZipStream\Header\CdrHeader;
use ZipStream\Header\CdrRecord;
use ZipStream\Header\FileHeader;

/**
 * Class ZipStream
 *
 * @package ZipStream
 */
class ZipStream implements ArchiveStreamInterface
{
    /**
     * @var FactsGathererCollection
     */
    private $factsGathererCollection;

    /**
     * @var FileOutputStreamerCollection
     */
    private $outputStreamerCollection;

    /**
     * @var resource
     */
    private $outputStream;

    /**
     * @var CdrRecord[]
     */
    private $cdrDirectory = [];

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $headerOffset = 0;

    /**
     * ZipStream constructor.
     * @param FactsGathererCollection      $factsGathererCollection
     * @param FileOutputStreamerCollection $outputStreamerCollection
     * @param resource                     $outputStream
     */
    public function __construct(
        FactsGathererCollection $factsGathererCollection,
        FileOutputStreamerCollection $outputStreamerCollection,
        $outputStream = null
    ) {
        $this->factsGathererCollection = $factsGathererCollection;
        $this->outputStreamerCollection = $outputStreamerCollection;
        $this->outputStream = $outputStream ?? fopen('php://output', 'rw');
    }

    /**
     * {@inheritDoc}
     */
    public function writeFile(FileInterface $file, bool $enabledDeflation)
    {
        /** @var DeflatedFactsGathererInterface|FactsGathererInterface $factsGatherer */
        $factsGatherer = $this->factsGathererCollection->getFor($file, $enabledDeflation);

        $facts = $enabledDeflation
            ? $factsGatherer->gatherDeflatedFacts($file)
            : $factsGatherer->gatherFacts($file);

        $fileHeader = new FileHeader($file, $facts);
        $cdrLength = $fileHeader->write($this->outputStream);

        $this->cdrDirectory[] = new CdrRecord($file, $facts, $this->offset);

        $this->offset += $cdrLength;

        $this->outputStreamerCollection->getFor($file)
            ->write($file, $this->outputStream, $enabledDeflation);
    }

    /**
     * {@inheritDoc}
     */
    public function finish(string $comment = '')
    {
        foreach ($this->cdrDirectory as $cdrRecord) {
            $cdrHeader = new CdrHeader($cdrRecord);
            $this->headerOffset += $cdrHeader->write($this->outputStream);
        }
        $cdrEOFHeader = new CdrEofHeader($this->offset, $this->headerOffset, count($this->cdrDirectory));
        $cdrEOFHeader->write($this->outputStream, $comment);
    }
}
