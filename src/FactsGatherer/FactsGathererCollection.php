<?php
declare(strict_types = 1);

namespace ZipStream\FactsGatherer;

use ZipStream\Exception\UnsupportedFileException;
use ZipStream\File\FileInterface;

/**
 * Class FactsGathererCollection
 * @package ZipStream\FactsGatherer
 */
class FactsGathererCollection
{
    /**
     * @var FactsGathererInterface[]
     */
    private $gatherers = [];

    /**
     * FactsGathererCollection constructor.
     * @param FactsGathererInterface[] ...$gatherers
     */
    public function __construct(FactsGathererInterface ...$gatherers)
    {
        $this->gatherers = $gatherers;
    }

    /**
     * @param FactsGathererInterface $gatherer
     */
    public function add(FactsGathererInterface $gatherer)
    {
        $this->gatherers[] = $gatherer;
    }

    /**
     * @param FactsGathererInterface[] ...$gatherers
     */
    public function set(FactsGathererInterface ...$gatherers)
    {
        $this->gatherers = $gatherers;
    }

    /**
     * @return FactsGathererInterface[]
     */
    public function get(): array
    {
        return $this->gatherers;
    }

    public function clear()
    {
        $this->gatherers = [];
    }

    /**
     * @param FileInterface $file
     * @param bool          $enabledDeflation
     * @return FactsGathererInterface
     * @throws UnsupportedFileException
     */
    public function getFor(FileInterface $file, bool $enabledDeflation): FactsGathererInterface
    {
        foreach ($this->gatherers as $gatherer) {
            if ($enabledDeflation && !$gatherer instanceof DeflatedFactsGathererInterface) {
                continue;
            }
            if ($gatherer->supports($file)) {
                return $gatherer;
            }
        }
        throw new UnsupportedFileException('There is no facts gatherer registered to collect the file facts.');
    }
}
