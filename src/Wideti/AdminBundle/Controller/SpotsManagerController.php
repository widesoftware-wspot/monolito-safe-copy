<?php


namespace Wideti\AdminBundle\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\SpotUser;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelperImp;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class SpotsManagerController
{

	/**
	 * @var EntityManager $em
	 */
	private $em;

	/**
	 * @var ControllerHelperImp
	 */
	private $controllerHelper;

	/**
	 * @var string
	 */
	private $managerPanelsAuthSecret;

	/**
	 * @var string
	 */
	private $kernelEnv;

	use SessionAware;
	use TwigAware;
	use SecurityAware;
	use PaginatorAware;

	public function __construct(
		EntityManager $em,
		ControllerHelperImp $controllerHelper,
		$managerPanelsAuthSecret,
		$kernelEnv
	) {
		$this->em = $em;
		$this->controllerHelper = $controllerHelper;
		$this->managerPanelsAuthSecret = $managerPanelsAuthSecret;
		$this->kernelEnv = $kernelEnv;
	}

	public function index(Request $request) {
		/**
		 * @var Users $user
		 */
		$user = $this->getUser();
		if (!$user->isSpotManager()) {
			throw new UnauthorizedHttpException("Not authorized");
		}

		$searchQuery = $request->get("search");
		$clients = $this->loadMySpots($user, $searchQuery);
		$tokens = $this->createTokensToRedirect($clients);

		$page = ($request->get("page") < 1) ? 1 : $request->get("page");
		$clientsPaginator = $this->paginator->paginate($clients, $page, 10);

		return $this->render("AdminBundle:SpotsManager:index.html.twig", [
			"clients" => $clientsPaginator,
			"tokens" => $tokens,
			"env" => $this->kernelEnv
		]);
	}

	/**
	 * Esta rota é para fazer o login automático em outro painel onde o cliente pode acessar
	 *
	 * @param Request $request
	 * @return RedirectResponse
	 * @throws OptimisticLockException
	 */
	public function authAction(Request $request)
	{
		// Pego os dados do token
		$resource = $this->getTokenResource($request);
		$userId = $resource['user_id'];
		$targetClientId = $resource['target_spot_id'];

		// Valida se o usuário em questão tem acesso ao painel do cliente solicitado
		if (!$this->canAccess($userId, $targetClientId)) {
			throw new UnauthorizedHttpException("No permission to switch your panel");
		}

		// carrega o user, não pode usar o da sessão pois ele estra logando em outro painel, precisa recarregar
		$user = $this->loadUserFromDB($userId);

		// carrega o target client
		$targetClient = $this->getLoggedClient();
		$targetDomain = $targetClient->getDomain();

		// autentica
		$this->doLogin($request, $user);

		// redireciona para o Dashboard
		$url = $this->prepareURL($targetDomain);
		return $this->controllerHelper->redirect($url);
	}

	public function logoutAction(Request $request) {

		/**
		 * @var Users $user
		 */
		$user = $this->getUser();
		$user->setSpotManagerLogged(false);
		$this->em->flush($user);

		$this->controllerHelper->getContainer()->get('security.token_storage')->setToken(null);
		$this->controllerHelper->getContainer()->get('request')->getSession()->invalidate();

		$sessionName = $this->controllerHelper->getContainer()->get('session.storage')->getName();

		$res = new RedirectResponse($this->controllerHelper->generateUrl("login_admin"));
		$res->headers->clearCookie($sessionName);
		return $res;
	}

	private function prepareURL($targetDomain) {
		return $this->kernelEnv == "prod"
			? "https://$targetDomain.wspot.com.br/admin/dashboard"
			: "http://$targetDomain.wspot.com.br/app_dev.php/admin/dashboard";
	}

	/**
	 * @param $userId
	 * @return Users
	 */
	private function loadUserFromDB($userId) {
		return $this
			->em
			->getRepository("DomainBundle:Users")
			->findOneBy([
				"id" => $userId
			]);
	}

	private function canAccess($userId, $targetClientId) {
		$result = $this
			->em
			->getRepository("DomainBundle:SpotUser")
			->findOneBy([
				"userId" => $userId,
				"clientId" => $targetClientId
			]);

		return !empty($result);
	}

	private function getTokenResource(Request $request) {
		$token = $request->request->get("token");
		return (array) JWT::decode($token, $this->managerPanelsAuthSecret, array('HS256'));
	}

	/**
	 * @param Client[] $clients
	 */
	private function createTokensToRedirect(array $clients) {
		$actualPanel = $this->getLoggedClient();
		$tokens = [];
		foreach ($clients as $c) {
			$t = JWT::encode([
				"user_id" => $this->getUser()->getId(),
				"target_spot_id" => $c->getId(),
				"origin_spot_id" => $actualPanel->getId(),
				"timestamp" => time()
			], $this->managerPanelsAuthSecret, 'HS256');
			$tokens[$c->getDomain()] = $t;
		}
		return $tokens;
	}

	private function loadMySpots(Users $user, $searchQuery = null) {
		/**
		 * @var SpotUser $spotsUsers[]
		 */
		$spotsUsers = $this
			->em
			->getRepository("DomainBundle:SpotUser")
			->findBy([
				"userId" => $user->getId()
			]);

		$ids = [];
		foreach ($spotsUsers as $su) {

			$ids[] = $su->getClientId();
		}

		return $this
			->em
			->getRepository('DomainBundle:Client')
			->spotsManagerFilter($searchQuery, $ids);
	}

	private function doLogin(Request $request, Users $user) {
		$passwordToken = new UsernamePasswordToken($user, $user->getPassword(), "public", $user->getRoles());

		$this->controllerHelper->getContainer()->get('security.token_storage')->setToken($passwordToken);
		$event = new InteractiveLoginEvent($request, $passwordToken);
		$this->controllerHelper->getContainer()->get('event_dispatcher')->dispatch('security.interactive_login', $event);
	}

}
