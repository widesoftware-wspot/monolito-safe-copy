<?php
namespace Wideti\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class LoginController extends Controller
{
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        $error = $session->get(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::AUTHENTICATION_ERROR);

		$session->set("panel_access", "manager");

        return $this->render('PanelBundle:Login:login.html.twig', array(
            'last_username' => $session->get(Security::LAST_USERNAME),
            'error'         => $error,
        ));
    }

    public function login2faAction(Request $request) {

    	$session = $request->getSession();
		$faHelper = $this->container->get('wspot.2fa.helper');
		$securityContext = $this->container->get('security.token_storage');
		$key = $faHelper->getSessionKey($session);

		$user = $securityContext->getToken()->getUser();

		if ($session->get($key) === true)
		{
			return $this->redirectToRoute("panel_client_list");
		}

		if ($request->getMethod() == 'POST')
		{
			//Check the authentication code
			if ($faHelper->checkCode($user, $request->get('_auth_code')) == true)
			{
				//Flag authentication complete
				$session->set($key, true);
				return $this->redirectToRoute("panel_client_list");
			}
			else
			{
				$session->getFlashBag()->set("error", "The verification code is not valid.");
			}
		}

		return $this->render('PanelBundle:Login:login2fa.html.twig');
	}
}
