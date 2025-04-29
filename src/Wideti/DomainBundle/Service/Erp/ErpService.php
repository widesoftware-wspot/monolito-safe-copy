<?php

namespace Wideti\DomainBundle\Service\Erp;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Erp\Dto\ErpTokenDto;

interface ErpService
{
    /**
     * @param Users $user
     * @return mixed
     */
    public function addContact(Users $user);

    /**
     * @param Users $user
     * @return mixed
     */
    public function removeContact(Users $user);

    /**
     * @param Users $user
     * @return ErpTokenDto
     */
    public function getToken(Users $user);

    /**
     * @param $id
     * @return mixed
     */
    public function getClientById($id);

    /**
     * @param $erpId
     * @return mixed
     */
    public function getClientErpDataById($erpId);

    /**
     * @param $erpId
     * @return mixed
     */
    public function unfreezeClientById($erpId);

    /**
     * @param $day
     * @return mixed
     */
    public function getClientsByClosingDate($day);

    /**
     * @return mixed
     */
    public function getOpenedCharges();

    /**
     * @param Client $client
     * @param $author
     * @return mixed
     */
    public function cancelAccount(Client $client, $author);

    /**
     * @return mixed
     */
    public function getSmsServiceItem();

    /**
     * @param $body
     * @return mixed
     */
    public function updateChargeWithSms($body);
}
