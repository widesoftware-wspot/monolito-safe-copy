<?php

namespace Wideti\DomainBundle\Service\Sns;

use Aws\Credentials\Credentials;
use Aws\Sns\SnsClient;

class SnsService
{
    /**
     * @var SnsClient
     */
    protected $sns;
    protected $arn;

    public function __construct($key, $secret, $arn, $region)
    {
        $credentials = new Credentials($key, $secret);

        $this->sns = new SnsClient([
            'version'     => '2010-03-31',
            'region'      => $region,
            'credentials' => $credentials
        ]);
        $this->arn = $arn;
    }

    public function getClient()
    {
        return $this->sns;
    }

    public function getArn()
    {
        return $this->arn;
    }
}
