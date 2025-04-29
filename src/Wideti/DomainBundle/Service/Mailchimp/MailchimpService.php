<?php

namespace Wideti\DomainBundle\Service\Mailchimp;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class MailchimpService
{
    use EntityManagerAware;

    private $apiKey;
    private $listId;
    private $client;

    public function __construct($apiKey, $listId)
    {
        $this->apiKey   = $apiKey;
        $this->listId   = $listId;
        $this->client   = new \Mailchimp($this->apiKey);
    }

    public function syncAdminUsersList()
    {
        $users = $this->em
            ->getRepository('DomainBundle:Users')
            ->getUsersToSyncMailchimp();

        $members    = null;
        $lists      = new \Mailchimp_Lists($this->client);
        $search     = $lists->members($this->listId);

        if (!$search) {
            return false;
        }

        $mailchimpList = [];

        for ($i=0; $i<intval($search['total'] / 100)+1; $i++) {
            $members = $lists->members(
                $this->listId,
                'subscribed',
                [
                    'start' => $i,
                    'limit' => 100
                ]
            );

            foreach ($members['data'] as $member) {
                array_push($mailchimpList, strtolower($member['email']));
            }
        }

        $localDiff      = array_diff($users, $mailchimpList);
        $mailchimpDiff  = array_diff($mailchimpList, $users);

        $totalSubscribe     = 0;
        $totalUnsubscribe   = 0;

        if (!empty($localDiff)) {
            $batch = [];
            foreach ($localDiff as $email) {
                $user       = $this->em->getRepository('DomainBundle:Users')->findOneByUsername($email);
                $name       = explode(' ', $user->getNome());
                $firstName  = $name[0];
                $lastName   = (end($name) != $name[0]) ? end($name) : '';

                array_push($batch, [
                    'email'      => ['email'=> $email],
                    'email_type' => 'html',
                    'merge_vars' => ['fname' => $firstName, 'lname' => $lastName]
                ]);
            }

            $subscribe      = $this->client->lists->batchSubscribe($this->listId, $batch, false, true, true);
            $totalSubscribe = $subscribe['add_count'];
        }

        if (!empty($mailchimpDiff)) {
            $batch = [];
            foreach ($mailchimpDiff as $email) {
                array_push($batch, [
                    'email' => $email
                ]);
            }

            $unsubscribe        = $this->client->lists->batchUnsubscribe($this->listId, $batch, true, false);
            $totalUnsubscribe   = $unsubscribe['success_count'];
        }

        return [
            'subscribers'   => $totalSubscribe,
            'unsubscribers' => $totalUnsubscribe
        ];
    }
}
