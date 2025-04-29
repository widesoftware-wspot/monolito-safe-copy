<?php

namespace Wideti\DomainBundle\Service\BulkInsert;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\BulkInsert\Dto\BulkResponse;

interface BulkInsertService
{
    /**
     * @param UploadedFile $file
     * @param Client $client
     * @return BulkResponse
     */
    public function process(UploadedFile $file, Client $client);
}
