<?php

namespace Wideti\DomainBundle\Tests\Helpers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Exception\DateInvalidException;
use Wideti\DomainBundle\Helpers\StringToDateTimeHelper;

class StringToDateTimeHelperTest extends WebTestCase
{
    public function testMustCreateDateFromCorrect()
    {
        $date = StringToDateTimeHelper::create("2018-01-01 10:10:10");
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals("2018-01-01 10:10:10", $date->format("Y-m-d H:i:s"));
    }

    public function testMustThrowDateInvalidExceptionOnInvalidDateFrom()
    {
        $this->expectException(DateInvalidException::class);
        StringToDateTimeHelper::create("asdasdasd");
    }

    public function testMustThrowDateInvalidExceptionOnNullInFromDate()
    {
        $this->expectException(DateInvalidException::class);
        StringToDateTimeHelper::create(null);
    }

    public function testMustCreateDateOnCustomFormat()
    {
        $format = "d/m/Y";
        $date = StringToDateTimeHelper::create('20/06/2018', $format);

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals("2018-06-20", $date->format("Y-m-d"));
    }

    public function testMustThrowExceptionIfCustomFormatReceiveBadString()
    {
        $this->expectException(DateInvalidException::class);
        $format = "d/m/Y";
        StringToDateTimeHelper::create('20-06-2018', $format);
    }
}