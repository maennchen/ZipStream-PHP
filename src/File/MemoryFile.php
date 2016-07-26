<?php
declare(strict_types = 1);

namespace ZipStream\File;

/**
 * Class MemoryFile
 * @package ZipStream\File
 */
class MemoryFile implements MemoryFileInterface
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var FileOptionsInterface
     */
    private $options;

    /**
     * @var string
     */
    private $data;

    /**
     * MemoryFile constructor.
     * @param string               $fileName
     * @param FileOptionsInterface $options
     * @param string               $data
     */
    public function __construct(string $fileName, string $data, FileOptionsInterface $options = null)
    {
        $this->fileName = $fileName;
        $this->data = $data;
        $this->options = $options ?? new NullFileOptions();
    }


    /**
     * Provide the name of the file
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return FileOptionsInterface
     */
    public function getOptions(): FileOptionsInterface
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }
}
