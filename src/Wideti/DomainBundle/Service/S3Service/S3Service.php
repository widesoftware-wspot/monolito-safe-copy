<?php

namespace Wideti\DomainBundle\Service\S3Service;

use Aws\Common\Credentials\Credentials;
use Aws\S3\S3Client;

class S3Service
{
    /**
     * @var String $awsKey
     */
    private $awsKey;
    /**
     * @var String $awsSecret
     */
    private $awsSecret;

    /**
     * S3Service constructor.
     * @param String $awsKey
     * @param String $awsSecret
     */
    public function __construct($awsKey, $awsSecret)
    {
        $this->awsKey = $awsKey;
        $this->awsSecret = $awsSecret;
    }

    /**
     * @param $awsRegion
     * @return S3Client
     */
    private function getS3Client()
    {
        $s3Credentials  = new \Aws\Credentials\Credentials($this->awsKey, $this->awsSecret);
        $s3Client       = new S3Client([
            'version' => '2006-03-01',
            'credentials'    => $s3Credentials,
            'region' => 'sa-east-1'
        ]);
        return $s3Client;
    }
}
