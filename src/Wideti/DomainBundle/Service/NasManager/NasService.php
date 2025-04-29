<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\GuestNotFoundException;
use Wideti\DomainBundle\Exception\NasEmptyException;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\FrontendBundle\Factory\NasFactory;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class NasService
{
    use SessionAware;
    use EntityManagerAware;
    use GuestServiceAware;
    use LoggerAware;

    /**
     * @var NasAccessCodeStep
     */
    public $accessCodeStep;

    /**
     * @var NasGuestGroupStep
     */
    public $guestGroupStep;

    /**
     * @var NasCampaignStep
     */
    public $campaignStep;

    /**
     * @var NasBusinessHoursStep
     */
    public $businessHoursStep;

    /**
     * @var NasRDIntegrationStep
     */
    public $rdIntegrationStep;

    /**
     * @var NasEgoiIntegrationStep
     */
    public $egoiIntegrationStep;

    /**
     * @var NasPolicyStep
     */
    public $policyStep;

    /**
     * @var NasAuthEventStep
     */
    public $authEventStep;

    /**
     * @var NasUpdateGuestLastAccessStep
     */
    public $updateGuestLastAccessStep;

    /**
     * @var FrontendControllerHelper
     */
    public $controllerHelper;

    /**
     * @param Guest $guest
     * @param $nas
     * @return mixed
     * @throws GuestNotFoundException
     * @throws NasEmptyException
     */
    public function process(Guest $guest, $nas, $canShowSecretAnswer = true, $canAskProgressiveFields = true)
    {
        if (!$guest) {
            throw new GuestNotFoundException('Guest not found on NasService::process');
        }

        if (!$nas) {
            try {
                $nas = $this->createNasManually($guest->getNasVendor(), $guest);
            } catch (\Exception $ex) {
                throw new NasEmptyException("Erro ao tentar montar o Nas no NasService::process. " .
                    "Guest: {$guest->getId()} - {$ex->getMessage()}");
            }
        }

        $client = $this->em
            ->getRepository("DomainBundle:Client")
            ->find($this->getLoggedClient()->getId());

        $this->guestService->returningGuest($guest);
        $this->session->set('guest_id', $guest->getId());

        $countVisits = $guest->getCountVisits();
        if ((($countVisits > 1 && $countVisits < 4) || $client->getAskRetroactiveGuestFields()) && $canAskProgressiveFields) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_progressive_form'));
        }

        if ($guest->getRegisterMode() == Social::OAUTH) {
            $canShowSecretAnswer = false;
        }
        if ($this->mustDefineSecretAnswer($client, $canShowSecretAnswer, $guest)){
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_security_answer'));
        }

        $nasStepManager = new NasStepManager();
        $nasStepManager->addStep($this->rdIntegrationStep);
        $nasStepManager->addStep($this->egoiIntegrationStep);
        $nasStepManager->addStep($this->accessCodeStep);
        $nasStepManager->addStep($this->guestGroupStep);
        $nasStepManager->addStep($this->businessHoursStep);
        $nasStepManager->addStep($this->policyStep);
        $nasStepManager->addStep($this->authEventStep);
        $nasStepManager->addStep($this->updateGuestLastAccessStep);
        $nasStepManager->addStep($this->campaignStep);
        return $nasStepManager->process($guest, $nas, $client);
    }

    /**
     * @param $vendorName
     * @param $guest
     * @return mixed
     * @throws NasEmptyException
     * @throws \ReflectionException
     * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
     */
    public function createNasManually($vendorName, $guest)
    {
        $nasFactory         = explode('-', $vendorName);
        $nasRawParameter    = NasHelper::decodeRawParametersToUrl($guest->getNasRaw());
        return NasFactory::factory($nasFactory[0], $nasRawParameter);
    }

    /**
     * @param Client $client
     * @param $canShowSecretAnswer
     * @param Guest $guest
     * @return bool
     */
    public function mustDefineSecretAnswer(Client $client, $canShowSecretAnswer, Guest $guest)
    {
        // TODO: QUANDO O RECURSO DE SEGURANÇA NA DEFINIÇÃO DA SENHA FOR EVOLUÍDO, DEVE SER REPENSADO ESTA LÓGICA
        // TODO: QUE FAZ O DESVIO DE FLUXO PARA O MÉTODO DE PERGUNTA SECRETA
        return $client->hasGuestPasswordRecoverySecurity() && $client->isEnablePasswordAuthentication() &&
            $canShowSecretAnswer && !$guest->isHasSecurityAnswer();
    }
}
