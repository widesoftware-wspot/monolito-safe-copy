<?php

namespace Wideti\DomainBundle\Service\Mail;

class MailHeaderService
{
    protected $sender;
    protected $adminRecipient;

    public function __construct(
        $email_sender,
        $email_admin_recipient,
        $email_financial_recipient,
        $email_commercial_recipient,
        $email_support_recipient,
        $email_developers_recipient
    ) {
        $this->sender               = $email_sender;
        $this->adminRecipient       = $email_admin_recipient;
        $this->financialRecipient   = $email_financial_recipient;
        $this->commercialRecipient  = $email_commercial_recipient;
        $this->supportRecipient     = $email_support_recipient;
        $this->developersRecipient  = $email_developers_recipient;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getAdminRecipient()
    {
        return $this->convertParamsToArray($this->adminRecipient);
    }

    public function getFinancialRecipient()
    {
        return $this->convertParamsToArray($this->financialRecipient);
    }

    public function getCommercialRecipient()
    {
        return $this->convertParamsToArray($this->commercialRecipient);
    }

    public function getDevelopersRecipient()
    {
        return $this->convertParamsToArray($this->developersRecipient);
    }

    public function getSupportRecipient()
    {
        return $this->supportRecipient;
    }

    /**
     * @param $params
     * @return array
     */
    private function convertParamsToArray($params)
    {
        $emails = [];

        if (!is_array($params)) {
            $emails[] = [$params];
        } else {
            foreach ($params as $email) {
                $emails[] = [$email];
            }
        }

        return $emails;
    }
}
