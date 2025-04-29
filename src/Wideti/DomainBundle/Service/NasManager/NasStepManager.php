<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

class NasStepManager
{
    /**
     * @var NasStepInterface[]
     */
    private $steps;

    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        foreach ($this->steps as $step) {
            $stepResult = $step->process($guest, $nas, $client);

            if ($stepResult) {
                return $stepResult;
            }
        }
    }

    public function addStep(NasStepInterface $nasStepInterface)
    {
        $this->steps[] = $nasStepInterface;
    }
}
