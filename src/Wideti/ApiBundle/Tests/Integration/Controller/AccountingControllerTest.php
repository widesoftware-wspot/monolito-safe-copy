<?php

namespace Wideti\ApiBundle\Tests\Integration\Controller;

use Wideti\ApiBundle\Tests\Integration\IntegrationTestCase;

class AccountingControllerTest extends IntegrationTestCase
{
    const DATE_FORMAT = 'Y-m-d H:i:s';

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testMustGetAccountingList()
    {
        $this->httpClient->request('GET', '/api/access');

        $response = $this->httpClient->getResponse();
        $result = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('totalRegistries', $result);
        $this->assertArrayHasKey('nextToken', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('guest', $result['data'][0]);
        $this->assertArrayHasKey('isEmployee', $result['data'][0]);
        $this->assertArrayHasKey('guestDevice', $result['data'][0]);
        $this->assertArrayHasKey('guestIp', $result['data'][0]);
        $this->assertArrayHasKey('nasIpAddress', $result['data'][0]);
        $this->assertArrayHasKey('identifier', $result['data'][0]);
        $this->assertArrayHasKey('friendlyName', $result['data'][0]);
        $this->assertArrayHasKey('start', $result['data'][0]);
        $this->assertArrayHasKey('stop', $result['data'][0]);
        $this->assertArrayHasKey('acctInputOctets', $result['data'][0]);
        $this->assertArrayHasKey('acctOutputOctets', $result['data'][0]);
        $this->assertArrayHasKey('download', $result['data'][0]);
        $this->assertArrayHasKey('upload', $result['data'][0]);
        $this->assertEquals(12, count(array_keys($result['data'][0])), "Quantidade de campos a mais do que o esperado");
    }

    public function testMustNotGenerateTokenIfResultIsEmpty()
    {
        $this->httpClient->request('GET', '/api/access?from=2001-05-25 00:00:00&to=2001-05-25 00:00:00');
        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(null, $content['nextToken']);
        $this->assertEquals(0, $content['totalRegistries']);
    }

    public function testMustFilterByIdentifier()
    {
        $identifier = $this->getValidFilters()['identifier'];
        $uri = "/api/access?identifier={$identifier}";
        $this->httpClient->request('GET', $uri);

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        //Caso retorne milhões de resultados, para evitar que o teste percorra todas as páginas.
        $maxPagination = 10;
        $pagesRequested = 0;

        do {
            $nextToken = $content['nextToken'];

            foreach ($content['data'] as $acct) {
                if ($acct['identifier'] !== $identifier) {
                    $this->fail("Encontrado identifier: {$acct['identifier']} diferente do filtrado: {$identifier}");
                }
            }

            if (!$nextToken || $pagesRequested >= $maxPagination) {
                break;
            }

            $this->httpClient->restart();
            $this->httpClient->request('GET', "/api/access?nextToken={$nextToken}");
            $response = $this->httpClient->getResponse();
            $content = json_decode($response->getContent(), true);

        } while(!empty($content['data']));
    }

    public function testMustFilterByGuest()
    {
        $guest = $this->getValidFilters()['guest'];
        $this->httpClient->request('GET', "/api/access?guest={$guest}");

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        //Caso retorne milhões de resultados, para evitar que o teste percorra todas as páginas.
        $maxPagination = 10;
        $pagesRequested = 0;

        do {
            $nextToken = $content['nextToken'];

            foreach ($content['data'] as $acct) {
                if ($acct['guest'] !== $guest) {
                    $this->fail("Encontrado guest: {$acct['guest']} diferente do filtrado: {$guest}");
                }
            }

            if (!$nextToken || $pagesRequested >= $maxPagination) {
                break;
            }

            $this->httpClient->restart();
            $this->httpClient->request('GET', "/api/access?nextToken={$nextToken}");
            $response = $this->httpClient->getResponse();
            $content = json_decode($response->getContent(), true);

        } while(!empty($content['data']));
    }

    public function testMustFilterByAllFilters()
    {
        $filters = $this->getValidFilters();
        $uri = "/api/access?guest={$filters['guest']}&identifier={$filters['identifier']}&from={$filters['from']}&to={$filters['to']}";

        $this
            ->httpClient
            ->request('GET', $uri);

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);


        //Caso retorne milhões de resultados, para evitar que o teste percorra todas as páginas.
        $maxPagination = 20;
        $pagesRequested = 0;

        do {
            $nextToken = $content['nextToken'];

            foreach ($content['data'] as $acct) {
                if ($acct['guest'] !== $filters['guest']) {
                    $this->fail("Encontrado guest: {$acct['guest']} diferente do filtrado: {$filters['guest']}");
                }

                if ($acct['identifier'] !== $filters['identifier']) {
                    $this->fail("Encontrado identifier: {$acct['identifier']} diferente do filtrado: {$filters['identifier']}");
                }

                $acctStart = \DateTime::createFromFormat(self::DATE_FORMAT, $acct['start'])->getTimestamp();
                $filterFrom = \DateTime::createFromFormat(self::DATE_FORMAT, $filters['from'])->getTimestamp();
                $filterTo = \DateTime::createFromFormat(self::DATE_FORMAT, $filters['to'])->getTimestamp();

                if ($acctStart < $filterFrom) {
                    $this->fail("Data de start do acct menor do que o filtro FROM");
                }

                if ($acctStart > $filterTo) {
                    $this->fail("Data de start do acct maior do que o filtro TO");
                }
            }

            if (!$nextToken || $pagesRequested >= $maxPagination) {
                break;
            }

            $this->httpClient->restart();
            $this->httpClient->request('GET', "/api/access?nextToken={$nextToken}");
            $response = $this->httpClient->getResponse();
            $content = json_decode($response->getContent(), true);

        } while(!empty($content['data']));
    }

    public function testMustOrderAsc()
    {
        $filters = $this->getValidFilters();
        $uri = "/api/access?order=asc";

        $this
            ->httpClient
            ->request('GET', $uri);

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);


        //Caso retorne milhões de resultados, para evitar que o teste percorra todas as páginas.
        $maxPagination = 20;
        $pagesRequested = 0;

        /**
         * @var \DateTime[] $startTime
         */
        $startTimeList = [];

        do {
            $nextToken = $content['nextToken'];

            foreach ($content['data'] as $acct) {
                $startTimeList[] = \DateTime
                    ::createFromFormat(self::DATE_FORMAT, $acct['start'])->getTimestamp();
            }

            if (!$nextToken || $pagesRequested >= $maxPagination) {
                break;
            }

            $this->httpClient->restart();
            $this->httpClient->request('GET', "/api/access?nextToken={$nextToken}");
            $response = $this->httpClient->getResponse();
            $content = json_decode($response->getContent(), true);

        } while(!empty($content['data']));

        $this->assertTrue($startTimeList[0] < $startTimeList[1], "Primeiro start é maior que o segundo start");
        $this->assertTrue($startTimeList[0] < $startTimeList[count($startTimeList) - 1], "Primeiro start maior que o último");
    }

    public function testMustOrderDesc()
    {
        $filters = $this->getValidFilters();
        $uri = "/api/access?order=desc";

        $this
            ->httpClient
            ->request('GET', $uri);

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);


        //Caso retorne milhões de resultados, para evitar que o teste percorra todas as páginas.
        $maxPagination = 20;
        $pagesRequested = 0;

        /**
         * @var \DateTime[] $startTime
         */
        $startTimeList = [];

        do {
            $nextToken = $content['nextToken'];

            foreach ($content['data'] as $acct) {
                $startTimeList[] = \DateTime
                    ::createFromFormat(self::DATE_FORMAT, $acct['start'])->getTimestamp();
            }

            if (!$nextToken || $pagesRequested >= $maxPagination) {
                break;
            }

            $this->httpClient->restart();
            $this->httpClient->request('GET', "/api/access?nextToken={$nextToken}");
            $response = $this->httpClient->getResponse();
            $content = json_decode($response->getContent(), true);

        } while(!empty($content['data']));

        $this->assertTrue($startTimeList[0] > $startTimeList[1], "Primeiro start é maior que o segundo start");
        $this->assertTrue($startTimeList[0] > $startTimeList[count($startTimeList) - 1], "Primeiro start maior que o último");
    }

    public function testMustBeEmptyIfFromIsGreatThenToDate()
    {
        $filters = $this->getValidFilters();
        $uri = "/api/access?from={$filters['to']}&to={$filters['from']}";

        $this
            ->httpClient
            ->request('GET', $uri);

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(null, $content['nextToken']);
        $this->assertEquals([], $content['data']);
        $this->assertEquals(0, $content['totalRegistries']);
    }

    public function testMustReturnNextTokenEvenResultNotHaveNextPage()
    {
        $filters = $this->getValidFilters();
        $uri = "/api/access?from={$filters['from']}&to={$filters['from']}&identifier={$filters['identifier']}";

        $this
            ->httpClient
            ->request('GET', $uri);

        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType("string", $content['nextToken']);
    }

    /**
     * @return array
     */
    private function getValidFilters()
    {
        $this->httpClient->request('GET', '/api/access?order=asc');
        $response = $this->httpClient->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->httpClient->restart();

        $filters = [
            'identifier' => $content['data'][0]['identifier'],
            'guest' => $content['data'][0]['guest'],
            'from' => $content['data'][0]['start'],
            'to' => $content['data'][count($content['data']) - 1]['start'],
        ];

        return $filters;
    }
}
