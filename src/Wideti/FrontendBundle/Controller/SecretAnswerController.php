<?php

namespace Wideti\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Service\NasManager\NasService;
use Wideti\DomainBundle\Service\SecretQuestion\Data\Answer;
use Wideti\DomainBundle\Service\SecretQuestion\SecretQuestionManagerInterface;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class SecretAnswerController implements NasControllerHandler
{
    use TwigAware;
    use SessionAware;
    use TemplateAware;

    /**
     * @var SecretQuestionManagerInterface
     */
    private $secretQuestionManager;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;

    /**
     * @var NasService
     */
    private $nasService;

    /**
     * @var GuestService
     */
    private $guestService;

    public function __construct(SecretQuestionManagerInterface $secretQuestionManager, FrontendControllerHelper $controllerHelper, NasService $nasService, GuestService $guestService)
    {
        $this->secretQuestionManager = $secretQuestionManager;
        $this->controllerHelper      = $controllerHelper;
        $this->nasService            = $nasService;
        $this->guestService          = $guestService;
    }

    public function createAction(Request $request)
    {
        /** @var Nas $sessionNas */
        $sessionNas = $this->session->get(Nas::NAS_SESSION_KEY);
        $guest_id = $this->session->get('guest_id');
        $guest = $this->guestService->getGuestById($guest_id);

        if ($request->getMethod() == "POST"){
            try {
                $client = $this->getLoggedClient();
                $answer = Answer::create(
                    $client->getId(),
                    $guest->getMysql(),
                    $request->get("question_id"),
                    $request->get("answer"));
                $this->secretQuestionManager->createSecretAnswer($answer);
                $this->guestService->setSecretQuestionAnswerd($guest);
            }catch (\Exception $e){
//                Se caso falhar no momento de salvar a pergunta secreta no MS, o fluxo deve seguir
            } finally {
                return $this->nasService->process($guest, $sessionNas, false, false);
            }
        }

        $listQuestions = $this->secretQuestionManager->getSecretQuestion();

        return $this->render(
            'FrontendBundle:SecretAnswer:registerAnswer.html.twig',
            [
                'guest'    => $guest,
                'template' => $this->templateService->templateSettings(
                    $this->session->get('campaignId')
                ),
                'listQuestions' => $listQuestions
            ]
        );
    }
}