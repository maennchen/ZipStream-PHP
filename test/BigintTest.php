<?php

namespace BigintTest;

use PHPUnit\Framework\TestCase;
use ZipStream\Bigint;
use OverflowException;

class ZipStreamTest extends TestCase
{
    public function testConstruct() {
        $bigint = new Bigint(0x12345678);
        $this->assertSame('0x0000000012345678', $bigint->getHex64());
        $this->assertSame(0x12345678, $bigint->getLow32());
        $this->assertSame(0, $bigint->getHigh32());
    }

    public function testConstructLarge() {
        $bigint = new Bigint(0x87654321);
        $this->assertSame('0x0000000087654321', $bigint->getHex64());
        $this->assertSame('87654321', bin2hex(pack('N', $bigint->getLow32())));
        $this->assertSame(0, $bigint->getHigh32());
    }

    public function testAddSmallValue() {
        $bigint = new Bigint(1);
        $bigint = $bigint->add(2);
        $this->assertSame(3, $bigint->getLow32());
    }

    public function testAddWithOverflowAtLowestByte() {
        $bigint = new Bigint(0xFF);
        $bigint = $bigint->add(0x01);
        $this->assertSame(0x100, $bigint->getLow32());
    }

    public function testAddWithOverflowAtInteger32() {
        $bigint = new Bigint(0xFFFFFFFF);
        $bigint = $bigint->add(0x01);
        $this->assertSame('0x0000000100000000', $bigint->getHex64());
    }

    public function testAddWithOverflowAtInteger64() {
        $bigint = Bigint::fromLowHigh(0xFFFFFFFF, 0xFFFFFFFF);
        $this->assertSame('0xFFFFFFFFFFFFFFFF', $bigint->getHex64());
        $this->expectException(OverflowException::class);
        $bigint->add(1);
    }

}
