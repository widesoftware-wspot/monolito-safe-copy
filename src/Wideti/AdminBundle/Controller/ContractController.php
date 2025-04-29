<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Wideti\DomainBundle\Entity\Contract;
use Wideti\DomainBundle\Service\Contract\ContractServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Symfony\Component\HttpFoundation\JsonResponse;

class ContractController
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use SessionAware;
    use ContractServiceAware;

    public function printAction(Contract $contract)
    {
        $contractText = $this->em
            ->getRepository('DomainBundle:Contract')
            ->findOneById($contract);

        $message = null;

        $text = $contractText->getText();

        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneById($this->getLoggedClient());

        $message = $this->contractService->replaceMessage(
            $text,
            [
                'client' => $client,
                'user'   => $this->getUser()
            ]
        );

        return $this->render(
            'AdminBundle:Contract:print.html.twig',
            array(
                'message' => $message,
                'isWhiteLabel' => $client->isWhiteLabel(),
            )
        );
    }

    public function userContractAcceptAction(Request $request)
    {
        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->findOneById($request->get('user'));

        $contract = $this->em
            ->getRepository('DomainBundle:Contract')
            ->findOneById($request->get('contract'));

        try {
            $this->contractService->accept($user, $contract);
        } catch (HttpException $error) {
            return new JsonResponse(
                [
                    'message' => $error->getMessage(),
                    'error' => true,
                ]
            );
        }

        $this->contractService->sendMail($user, $contract);

        return new JsonResponse(
            [
                'message' => 'Sucesso',
            ]
        );
    }
}
