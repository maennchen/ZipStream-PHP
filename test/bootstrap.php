<?php
use Symfony\Bridge\PhpUnit\ClockMock;
use ZipStream\Header\CdrHeader;
use ZipStream\Header\HeaderHelperTrait;
use ZipStream\Test\Unit\Header\CdrHeaderTest;
use ZipStream\Test\Unit\Header\FileHeaderTest;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

ClockMock::register(CdrHeaderTest::class);
ClockMock::register(CdrHeader::class);
ClockMock::register(HeaderHelperTrait::class);
ClockMock::register(FileHeaderTest::class);
ClockMock::withClockMock(strtotime('2015-01-01 01:00:00'));
