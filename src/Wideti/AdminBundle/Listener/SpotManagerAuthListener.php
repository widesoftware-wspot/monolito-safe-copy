<?php


namespace Wideti\AdminBundle\Listener;


use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelperImp;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SpotManagerAuthListener
{

	/**
	 * @var EntityManager $em
	 */
	private $em;
	/**
	 * @var ControllerHelperImp
	 */
	private $controllerHelper;

	use SessionAware;
	use SecurityAware;

	public function __construct(EntityManager $em, ControllerHelperImp $controllerHelper)
	{
		$this->em = $em;
		$this->controllerHelper = $controllerHelper;
	}

	public function onKernelRequest(GetResponseEvent $event)
	{
		if (!$event->isMasterRequest()) {
			return;
		}

		if (!$this->isAdminPanelURI($event)) {
			return;
		}

		/**
		 * @var Users $principal
		 */
		$principal = $this->getUser();
		if ($principal !== null && !$principal->isSpotManager()) {
			return;
		}

		if ($principal !== null && !$this->spotManagerIsLogged($principal)) {
			$redirectResponse = new RedirectResponse($this->controllerHelper->generateUrl("spots_manager_logout"));
			$event->setResponse($redirectResponse);
		}
	}

	/**
	 * @param GetResponseEvent $event
	 * @return bool
	 */
	private function isAdminPanelURI(GetResponseEvent $event) {

		$uri = $event->getRequest()->getPathInfo();

		if (preg_match("/^\/admin\/logout/i", $uri)) {
			return false;
		}

		if (preg_match("/^\/admin\/spots-manager\/logout/i", $uri)) {
			return false;
		}

		return (boolean) preg_match("/^\/admin/i", $uri);
	}

	private function spotManagerIsLogged(Users $principal) {

		$res = $this
			->em
			->getRepository("DomainBundle:Users")
			->findOneBy([
				'id' => $principal->getId(),
				'spotManagerLogged' => true,
				'spotManager' => true,
			]);

		return (boolean) $res != null;

	}
}