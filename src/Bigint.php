<?php

namespace ZipStream;

use OverflowException;

class Bigint
{
    private $bytes = [0, 0, 0, 0, 0, 0, 0, 0];

    public function __construct($value = 0)
    {
        if ($value instanceof self) {
            $this->bytes = $value->bytes;
        } else {
            $this->fillBytes($value, 0, 8);
        }
    }

    public function getValue($end = 0, $length = 8)
    {
        $result = 0;
        for ($i = $end+$length-1; $i >= $end; $i--) {
            $result <<= 8;
            $result |= $this->bytes[$i];
        }
        return $result;
    }

    public function getLow32()
    {
        return $this->getValue(0, 4);
    }

    public function getHigh32()
    {
        return $this->getValue(4, 4);
    }

    public function isOver32($force = false)
    {
        // value 0xFFFFFFFF already needs a Zip64 header
        return $force ||
            max(array_slice($this->bytes, 4, 4)) > 0 ||
            min(array_slice($this->bytes, 0, 4)) == 0xFF;
    }

    public function getLowFF($force = false)
    {
        if ($force || $this->isOver32()) {
            return 0xFFFFFFFF;
        } else {
            return $this->getLow32();
        }
    }

    public function getHex64()
    {
        $result = '0x';
        for ($i = 7; $i >= 0; $i--) {
            $result .= sprintf('%02X', $this->bytes[$i]);
        }
        return $result;
    }

    public function add($other)
    {
        if (!$other instanceof self) {
            $other = new self($other);
        }
        $result = clone $this;
        $overflow = false;
        for ($i=0; $i<8; $i++) {
            $result->bytes[$i] += $other->bytes[$i];
            if ($overflow) {
                $result->bytes[$i]++;
                $overflow = false;
            }
            if ($result->bytes[$i] & 0x100) {
                $overflow = true;
                $result->bytes[$i] &= 0xFF;
            }
        }
        if ($overflow) {
            throw new OverflowException;
        }
        return $result;
    }

    public static function init($value = 0)
    {
        $bigint = new Bigint($value);
        return $bigint;
    }

    public static function fromLowHigh($low, $high)
    {
        $bigint = new Bigint;
        $bigint->fillBytes($low, 0, 4);
        $bigint->fillBytes($high, 4, 4);
        return $bigint;
    }

    protected function fillBytes($value, $start, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->bytes[$start+$i] = $i >= PHP_INT_SIZE ? 0 : $value & 0xFF;
            $value >>= 8;
        }
    }
}
