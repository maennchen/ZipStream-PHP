<?php

declare(strict_types=1);

namespace ZipStream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * @internal
 */
class StaticStream implements StreamInterface
{
    private int $offset = 0;

    public function __construct(private string $data)
    {
    }

    public function __toString(): string
    {
        return $this->data;
    }

    public function close(): void
    {
        $this->detach();
    }

    public function detach(): string
    {
        $result = $this->data;
        $this->data = '';
        return $result;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        switch($whence) {
            case SEEK_SET:
                $this->offset = $offset;
                break;
            case SEEK_CUR:
                $this->offset += $offset;
                break;
            case SEEK_END:
                $this->offset = strlen($this->data) + $offset;
                break;
        }
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function getMetadata($key = null)
    {
        return $key !== null ? null : [];
    }

    public function getSize(): int
    {
        return strlen($this->data);
    }

    public function tell(): int
    {
        return $this->offset;
    }

    public function eof(): bool
    {
        return $this->offset >= strlen($this->data);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function write($string): int
    {
        throw new RuntimeException();
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function read($length): string
    {
        $data = substr($this->data, $this->offset, $length);
        $this->offset+= $length;
        return $data;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function getContents(): string
    {
        $data = substr($this->data, $this->offset);
        $this->offset = strlen($this->data);
        return $data;
    }
}
