<?php

namespace Wideti\PanelBundle\Controller;

use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\PanelBundle\Service\WhitelabelCertService;

use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Symfony\Component\HttpFoundation\Request;

class WhitelabelCertController
{
    use TwigAware;
    use FormAware;
    use RouterAware;
    use LoggerAware;
    use FlashMessageAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var WhitelabelCertService
     */
    private $whitelabelCertService;

    /**
     * SmsGatewayController constructor.
     * @param FrontendControllerHelper $controllerHelper
     * @param WhitelabelCertService $whitelabelCertService
     */
    public function __construct(
        FrontendControllerHelper $controllerHelper,
        WhitelabelCertService $whitelabelCertService
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->whitelabelCertService = $whitelabelCertService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function newAction(Request $request)
    {
        // Render the form for entering a new domain
        return $this->render('PanelBundle:WhitelabelCert:new.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function generateCertAction(Request $request)
    {
        $domain = $request->request->get('domain');

        $response = $this->whitelabelCertService->generateCert($domain);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 200) {
            $message = 'OK - Certificado gerado com Sucesso.';
        } else {
            $bodyContent = $response->getBody()->getContents();
            $message = str_replace("exec error: exit status 1", "", $bodyContent);
        }

        return $this->render('PanelBundle:WhitelabelCert:result.html.twig', [
            'message' => $message,
        ]);
    }
}