<?php


namespace Wideti\AdminBundle\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientsLegalBase;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class LegalBaseManagerController
{
    use TwigAware;
    use SessionAware;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    public function __construct(LegalBaseManagerService $legalBaseManager, AuthorizationChecker $authorizationChecker)
    {
        $this->legalBaseManager = $legalBaseManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function indexAction(Request $request)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
        /**
         * @var $client Client
         */
        $client     = $this->getLoggedClient();

        if ($client->getNoRegisterFields()) {
            $legalKinds =[ $this->legalBaseManager->getLegalKind(LegalKinds::LEGITIMO_INTERESSE)];
        } else {
            $legalKinds = $this->legalBaseManager->getAllLegalKinds();
        }
        $traceHeaders = TracerHeaders::from($request);

        if ($request->getMethod() == "POST"){
            if ($request->get("force_disable") == LegalKinds::TERMO_CONSENTIMENTO){
                $this->legalBaseManager->forceDisableConsentTerm($client, $traceHeaders);
            }else{
                $this->legalBaseManager->defineLegalBase($client, $request->get('legalKindKey'), $traceHeaders);
            }
        }

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
        $mustShowForceDisable = $this->mustShowForceDisable($client, $activeLegalBase, $traceHeaders);

        return $this->render(
            'AdminBundle:LegalBaseManager:index.html.twig',
            [
                'legalKinds' => $legalKinds,
                'activeLegalBase' => $activeLegalBase,
                'mustShowForceDisable' => $mustShowForceDisable,
                'client' => $client
            ]
        );
    }

    private function mustShowForceDisable(Client $client, ClientsLegalBase $activeLegalBase, $traceHeaders = [])
    {
        $legalBase = $activeLegalBase->getLegalKind();
        return ($this->legalBaseManager->hasConsentTerm($client, $traceHeaders)
            && $legalBase->getKey() == LegalKinds::LEGITIMO_INTERESSE
            && $this->authorizationChecker->isGranted('ROLE_MANAGER')
        );
    }
}