<?php

namespace Wideti\DomainBundle\Tests\Service\Radacct\Dto;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\DateInvalidException;
use Wideti\DomainBundle\Exception\InvalidGuestIdException;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

class AcctStreamFilterDtoTest extends WebTestCase
{

    public function testMustCreateFilterFromRequest()
    {
        $request = $this->getRequestMock([
            "identifier" => "11-11-11-11-11-11",
            "guest" => 234,
            "nextToken" => "TTTERGSSUSUE&W&WREW#$#33",
            "order" => "asc",
            "from" => "2018-01-01 00:00:00",
            "to" => "2018-01-20 23:59:59"
        ]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));

        $this->assertInstanceOf(AcctStreamFilterDto::class, $filter);
        $this->assertEquals("11-11-11-11-11-11", $filter->getIdentifier());
        $this->assertEquals(234, $filter->getGuest());
        $this->assertEquals("TTTERGSSUSUE&W&WREW#$#33", $filter->getNextToken());
        $this->assertEquals("asc", $filter->getOrder());
        $this->assertInstanceOf(\DateTime::class, $filter->getFrom());
        $this->assertEquals("2018-01-01 00:00:00", $filter->getFrom()->format("Y-m-d H:i:s"));
        $this->assertInstanceOf(\DateTime::class, $filter->getTo());
        $this->assertEquals("2018-01-20 23:59:59", $filter->getTo()->format("Y-m-d H:i:s"));
        $this->assertInstanceOf(Client::class, $filter->getClient());
        $this->assertEquals(1, $filter->getClient()->getId());
    }

    public function testMustThrowDateInvalidExceptionIfDateFromIsInvalid()
    {
        $this->setExpectedException(DateInvalidException::class);

        $request = $this->getRequestMock([
            "from" => "2017-02/30 12:34:10"
        ]);

        AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));
    }

    public function testMustThrowDateInvalidExceptionIfDateToIsInvalid()
    {
        $this->setExpectedException(DateInvalidException::class);

        $request = $this->getRequestMock([
            "to" => "2017-02/30 12:34:10"
        ]);

        AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));
    }

    public function testMustReturnDatesNullWhenNotExistsInFilter()
    {
        $request = $this->getRequestMock([]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));

        $this->assertInstanceOf(AcctStreamFilterDto::class, $filter);
        $this->assertEquals(null, $filter->getFrom());
        $this->assertEquals(null, $filter->getTo());
    }

    public function testMustReturnAllParametersNullIfFiltersNotExists()
    {
        $request = $this->getRequestMock([]);
        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));

        $this->assertInstanceOf(AcctStreamFilterDto::class, $filter);
        $this->assertEquals(null, $filter->getFrom());
        $this->assertEquals(null, $filter->getTo());
        $this->assertEquals(null, $filter->getOrder());
        $this->assertEquals(null, $filter->getNextToken());
        $this->assertEquals(null, $filter->getGuest());
        $this->assertEquals(null, $filter->getIdentifier());
    }

    public function testMustGuestIdBeIntegerInsideDto()
    {
        $request = $this->getRequestMock([
            "guest" => "33"
        ]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));

        $this->assertInternalType("int", $filter->getGuest());
    }

    public function testMustThrowExceptionIfGuestIdIsNotInteger()
    {
        $this->setExpectedException(InvalidGuestIdException::class);
        $request = $this->getRequestMock([
            "guest" => "invalid_integer_id"
        ]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));
    }

    public function testMustThrowExceptionIfGuestIdIsStringWithInteger()
    {
        $this->setExpectedException(InvalidGuestIdException::class);
        $request = $this->getRequestMock([
            "guest" => "33ab"
        ]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));
    }

    public function testMustThrowExceptionIfGuestIdFloat()
    {
        $this->setExpectedException(InvalidGuestIdException::class);
        $request = $this->getRequestMock([
            "guest" => 22.6
        ]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));
    }

    public function testMustThrowExceptionIfGuestIdIsFloatString()
    {
        $this->setExpectedException(InvalidGuestIdException::class);
        $request = $this->getRequestMock([
            "guest" => "22.4"
        ]);

        $filter = AcctStreamFilterDto::createFromRequest($request, Client::createClientWithId(1));
    }

    /**
     * @param array $params
     * @return Request
     */
    private function getRequestMock(array $params)
    {
        $request = new Request();
        $request->attributes->add($params);
        return $request;
    }
}
