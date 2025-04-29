<?php

namespace Wideti\DomainBundle\Service\Contract;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Contract;
use Wideti\DomainBundle\Entity\ContractUser;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ContractService
{
    use EntityManagerAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;
    use SessionAware;
    use SecurityAware;

    public function accept(Users $user, Contract $contract)
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $contractUser = new ContractUser();

        $contractUser->setContract($contract);
        $contractUser->setUser($user);
        $contractUser->setFingerprint("IP: ".$ip." - ".$_SERVER['HTTP_USER_AGENT']);

        $this->em->persist($contractUser);
        $this->em->flush();
    }

    public function sendMail(Users $user, Contract $contract)
    {
        $type = null;

        /**
         * @var Client $client
         */

        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneById($this->getLoggedClient());

        if ($contract->getType() == 1) {
            if ($client->isWhiteLabel()) {
                $type = 'envio de SMS pelo Hotspot';
            } else {
                $type = 'envio de SMS pela Mambo Wifi';
            }

        }

        $subject = 'Mambo Wifi - Ativação de '.$type;
        if ($client->isWhiteLabel()) {
            $subject = 'Hotspot - Ativação de '.$type;
        }

        $text = $this->replaceMessage(
            $contract->getText(),
            [
                'client' => $client,
                'user'   => $this->getUser()
            ]
        );

        $emailsTo   = $this->emailHeader->getFinancialRecipient();
        $emailsTo[] = [$user->getUsername()];

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($subject)
            ->from(['Ativação de uso de SMS' => $this->emailHeader->getSender()])
            ->to($emailsTo)
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Contract:acceptContract.html.twig',
                    [
                        'user'     => $user,
                        'contract' => $contract,
                        'message'  => $text,
                        'type'     => $type,
                        'smsCost' => $client->getSmsCost(),
                        'isWhiteLabel'   => $client->isWhiteLabel(),
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    public function replaceMessage($text, $args = null)
    {
        /**
         * @var Client $client
         */
        $client = $args['client'];
        /**
         * @var Users $user
         */
        $user   = $args['user'];

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $search = [
            '{{ clientName }}',
            '{{ clientAddress }}',
            '{{ clientDocument }}',
            '{{ sms_cost }}',
            '{{ userName }}',
            '{{ userEmail }}',
            '{{ userIp }}',
            '{{ date }}'
        ];

        $replace = [
            $client->getCompany(),
            $client->getAddress().", ".$client->getDistrict()." - ".$client->getCity()."/"
            .$client->getState().", CEP: ".$client->getZipCode(),
            $client->getDocument(),
            $client->getSmsCost(),
            $user->getNome(),
            $user->getUsername(),
            $ip,
            date('d/m/Y H:i:s'),
        ];

        $message = str_replace($search, $replace, $text);

        return $message;
    }
}
