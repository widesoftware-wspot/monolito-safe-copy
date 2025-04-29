<?php

namespace Wideti\PanelBundle\Controller;

use Wideti\DomainBundle\Service\Mailchimp\MailchimpServiceAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class MailchimpController
{
    use TwigAware;
    use RouterAware;
    use MailchimpServiceAware;

    public function indexAction(Request $request)
    {
        $sync = false;

        if ($request->get('sync')) {
            $sync = $this->mailchimpService->syncAdminUsersList();
        }

        return $this->render(
            'PanelBundle:Mailchimp:index.html.twig',
            array(
                'sync' => $sync
            )
        );
    }

    public function syncUsersList()
    {
        $sync = $this->mailchimpService->syncAdminUsersList();
        return new JsonResponse($sync, 200);
    }
}
