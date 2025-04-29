<?php
/**
 * Created by PhpStorm.
 * User: wideti
 * Date: 16/01/19
 * Time: 13:05
 */

namespace Wideti\FrontendBundle\Controller;


use phpDocumentor\Reflection\Types\This;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Dto\OneGuestQueryDto;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\FrontendBundle\DependencyInjection\Configuration;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;



class SurveyAuthController extends Controller implements NasControllerHandler
{
    const SURVEY_ANSWERED_SESSION_KEY = "survey_answered";
    use SessionAware;
    use LoggerAware;
    use NasServiceAware;
    use GuestServiceAware;

    private $controllerHelper;
    private $configurationService;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var EventLoggerManager
     */
    private $logManager;

    public function __construct(ControllerHelper $controllerHelper,
                                ConfigurationService $configurationService,
                                EventLoggerManager $logManager,
                                ClientService  $clientService
    ){
        $this->controllerHelper     = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->logManager           = $logManager;
        $this->clientService        = $clientService;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function surveyStep(Request $request)
    {
        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::SURVEY_LOGIN)
            ->withEventType(EventType::SURVEY_LOGIN)
            ->withNas($this->session->get(Nas::NAS_SESSION_KEY))
            ->withRequest(null)
            ->withSession($this->session)
            ->withExtraData($this->session->get('guest'))
            ->build();

        $this->logManager->sendLog($event);

        if (!$this->session->get(Nas::NAS_SESSION_KEY)) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        if(is_null($nas->getRadiusPolicy()->getGuest()->getUsername()) ) {
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $guestId = $nas->getRadiusPolicy()->getGuest()->getUsername();

        $oneGuestDto = new OneGuestQueryDto();
        $oneGuestDto->setMysql($guestId);
        $guest = $this->guestService->getOneGuest($oneGuestDto);

        $nas =  $this->session->get(Nas::NAS_SESSION_KEY);

        $this->session->set(SurveyAuthController::SURVEY_ANSWERED_SESSION_KEY, true);

        return $this->nasService->process($guest, $nas);
    }
}
