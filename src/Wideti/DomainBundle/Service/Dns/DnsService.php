<?php

namespace Wideti\DomainBundle\Service\Dns;

use Aws\Route53\Route53Client;

class DnsService
{
    protected $aws_key;
    protected $aws_secret;

    public function __construct($aws_key, $aws_secret)
    {
        $this->aws_key    = $aws_key;
        $this->aws_secret = $aws_secret;
    }

    public function create($subdomain)
    {
        $subdomain = $subdomain.'.wspot.com.br';

        $client = Route53Client::factory(
            [
                'key'    => $this->aws_key,
                'secret' => $this->aws_secret
            ]
        );

        $client->changeResourceRecordSets(
            [
                'HostedZoneId' => 'Z2PTG5VCCAG2YL',
                'ChangeBatch' => array(
                    'Changes' => array(
                        array(
                            'Action' => 'CREATE',
                            'ResourceRecordSet' => array(
                                'Name' => $subdomain,
                                'Type' => 'CNAME',
                                'TTL' => 600,
                                'ResourceRecords' => array(
                                    array(
                                        'Value' => 'WSPOT-NGINX-430318580.sa-east-1.elb.amazonaws.com.',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ]
        );

    }

}
