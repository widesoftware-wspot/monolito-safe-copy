<?php
namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

interface NasStepInterface
{
    const NO_RETURN = null;
    public function process(Guest $guest, Nas $nas = null, Client $client);
}
