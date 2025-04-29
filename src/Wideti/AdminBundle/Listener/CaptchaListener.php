<?php
namespace Wideti\AdminBundle\Listener;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;
use Wideti\AdminBundle\Form\LoginType;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;


/**
 * Captcha listener.
 */
class CaptchaListener
{
    private $formFactory;
    private $controllerHelper;
    private $twig;
    use SessionAware;
    use EntityManagerAware;

    public function __construct(
        FormFactoryInterface $formFactory,
        AdminControllerHelper $controllerHelper,
        $twig
    ) {
        $this->formFactory = $formFactory;
        $this->controllerHelper = $controllerHelper;
        $this->twig = $twig;
    }

    private function getOauthLoginSource($client)
    {
        $clientErpId = $client->getErpId();
        return $this->em
        ->getRepository("DomainBundle:AdminOAuthLogin")
        ->findOneBy(['erpId' => $clientErpId]);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if ($request->isMethod('POST') && $request->getPathInfo() === '/admin/login_check') {
            $form = $this->formFactory->create(LoginType::class);
            $form->handleRequest($event->getRequest());

            if ($form->isSubmitted() && !$form->isValid()) {
                $client = $this->getLoggedClient();
                $event->setResponse(new Response(
                    $this->twig->render('AdminBundle:Login:login.html.twig', array(
                        'form'          => $form->createView(),
                        'last_username' => $session->get(Security::LAST_USERNAME),
                        'error'         => null,
                        'blockedTime'   => null,
                        'isWhiteLabel' => false,
                        'oauth'         => $this->getOauthLoginSource($client),
                        'oAuthError'    => $request->get('oAuthError'),
                        'autoLoginError'=> null
                    ))
                ));
            }
        }
    }
}
