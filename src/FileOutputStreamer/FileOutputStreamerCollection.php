<?php
declare(strict_types = 1);

namespace ZipStream\FileOutputStreamer;

use ZipStream\Exception\UnsupportedFileException;
use ZipStream\File\FileInterface;

/**
 * Class FileOutputStreamerCollection
 * @package ZipStream\FileOutputStreamer
 */
class FileOutputStreamerCollection
{
    /**
     * @var FileOutputStreamerInterface[]
     */
    private $streamers = [];

    /**
     * FileOutputStreamerCollection constructor.
     * @param FileOutputStreamerInterface[] ...$streamers
     */
    public function __construct(FileOutputStreamerInterface ...$streamers)
    {
        $this->streamers = $streamers;
    }

    /**
     * @param FileOutputStreamerInterface $streamer
     */
    public function add(FileOutputStreamerInterface $streamer)
    {
        $this->streamers[] = $streamer;
    }

    /**
     * @param FileOutputStreamerInterface[] ...$streamers
     */
    public function set(FileOutputStreamerInterface ...$streamers)
    {
        $this->streamers = $streamers;
    }

    /**
     * @return FileOutputStreamerInterface[]
     */
    public function get(): array
    {
        return $this->streamers;
    }

    public function clear()
    {
        $this->streamers = [];
    }

    /**
     * @param FileInterface $file
     * @return FileOutputStreamerInterface
     * @throws UnsupportedFileException
     */
    public function getFor(FileInterface $file): FileOutputStreamerInterface
    {
        foreach ($this->streamers as $streamer) {
            if ($streamer->supports($file)) {
                return $streamer;
            }
        }
        throw new UnsupportedFileException('There is no output streamer registered to stream the file.');
    }
}
