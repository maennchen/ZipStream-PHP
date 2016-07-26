<?php
declare(strict_types = 1);

namespace ZipStream\Test\Unit\FactsGatherer;

use PHPUnit_Framework_TestCase;
use ZipStream\FactsGatherer\DeflatedFactsGathererInterface;
use ZipStream\FactsGatherer\FactsGathererCollection;
use ZipStream\FactsGatherer\FactsGathererInterface;
use ZipStream\File\FileInterface;

/**
 * Class FactsGathererCollectionTest
 * @package ZipStream\Test\Unit\FactsGatherer
 */
class FactsGathererCollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructAndGet()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer2 = $this->createMock(FactsGathererInterface::class);

        $collection = new FactsGathererCollection($gatherer1, $gatherer2);

        static::assertEquals([$gatherer1, $gatherer2], $collection->get());
    }

    public function testAdd()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer2 = $this->createMock(FactsGathererInterface::class);
        $gatherer3 = $this->createMock(FactsGathererInterface::class);

        $collection = new FactsGathererCollection($gatherer1, $gatherer2);
        $collection->add($gatherer3);

        static::assertEquals([$gatherer1, $gatherer2, $gatherer3], $collection->get());
    }

    public function testSet()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer2 = $this->createMock(FactsGathererInterface::class);
        $gatherer3 = $this->createMock(FactsGathererInterface::class);

        $collection = new FactsGathererCollection($gatherer1, $gatherer2);
        $collection->set($gatherer3);

        static::assertEquals([$gatherer3], $collection->get());
    }

    public function testClear()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer2 = $this->createMock(FactsGathererInterface::class);

        $collection = new FactsGathererCollection($gatherer1, $gatherer2);
        $collection->clear();

        static::assertEquals([], $collection->get());
    }

    public function testGetForDeflated()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer1->method('supports')
            ->willReturn(false);
        $gatherer2 = $this->createMock(DeflatedFactsGathererInterface::class);
        $gatherer2->method('supports')
            ->willReturn(true);
        $gatherer3 = $this->createMock(FactsGathererInterface::class);
        $gatherer3->method('supports')
            ->willReturn(true);

        $collection = new FactsGathererCollection($gatherer1, $gatherer2, $gatherer3);

        $file = $this->createMock(FileInterface::class);

        static::assertEquals($gatherer2, $collection->getFor($file, true));
    }

    public function testGetForUndeflated()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer1->method('supports')
            ->willReturn(false);
        $gatherer2 = $this->createMock(FactsGathererInterface::class);
        $gatherer2->method('supports')
            ->willReturn(true);

        $collection = new FactsGathererCollection($gatherer1, $gatherer2);

        $file = $this->createMock(FileInterface::class);

        static::assertEquals($gatherer2, $collection->getFor($file, false));
    }

    /**
     * @expectedException ZipStream\Exception\UnsupportedFileException
     * @expectedExceptionMessage There is no facts gatherer registered to collect the file facts.
     */
    public function testGetForNoGatherers()
    {
        $collection = new FactsGathererCollection();

        $file = $this->createMock(FileInterface::class);

        $collection->getFor($file, false);
    }

    /**
     * @expectedException ZipStream\Exception\UnsupportedFileException
     * @expectedExceptionMessage There is no facts gatherer registered to collect the file facts.
     */
    public function testGetForUnsupportedFile()
    {
        $gatherer1 = $this->createMock(FactsGathererInterface::class);
        $gatherer1->method('supports')
            ->willReturn(false);

        $collection = new FactsGathererCollection($gatherer1);

        $file = $this->createMock(FileInterface::class);

        $collection->getFor($file, false);
    }
}
