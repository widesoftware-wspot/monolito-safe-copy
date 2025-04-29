<?php

namespace Wideti\DomainBundle\Service\Mailer\Providers;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Wideti\DomainBundle\Service\Mailer\MailReturnStatus;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessage;
use Wideti\DomainBundle\Service\Mailer\Message\ProviderMessageFactory;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class SesProvider implements Provider
{
    use LoggerAware;

    /**
     * @var SesClient
     */
    private $ses;

    public function __construct($aws_ses_key, $aws_ses_secret, $aws_ses_region)
    {
        $this->ses = new SesClient([
            'version' => '2010-12-01',
            'credentials' => ['key'=>$aws_ses_key, 'secret' => $aws_ses_secret],
            'region' => $aws_ses_region
        ]);
    }

    /**
     * @param MailMessage $message
     * @return MailReturnStatus
     */
    public function send(MailMessage $message)
    {
        $sesMessage = ProviderMessageFactory::createSesMessage($message);
        $send = $this->ses->sendRawEmail($sesMessage);

        $returnStatus = new MailReturnStatus();
        $returnStatus->setMessageId($send->toArray()['MessageId']);
        $returnStatus->setEmail($message->getTo()[0]['email']);
        $returnStatus->setStatus(true);
        $returnStatus->setRejectedReason(null);

        return $returnStatus;
    }

    /**
     * @param string $email
     * @return \Aws\Result
     */
    public function validateSendEmail($email)
    {
        try {
            $result = $this->ses->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);
            return $result;
        } catch (AwsException $e) {
            // output error message if fails
            $this->logger->addCritical($e->getMessage());
        }
    }

    /**
     * @return \Aws\Result|null
     */
    public function verifyEmailSender()
    {
        try {
            $result = $this->ses->listIdentities([
                'IdentityType' => 'EmailAddress',
            ]);
            return $result;
        } catch (AwsException $e) {
            // output error message if fails
            $this->logger->addCritical($e->getMessage());
        }
        return null;
    }

    public function validateSender($email)
    {
        $result = $this->verifyEmailSender();
        $validatedEmails =  isset($result['Identities']) ? $result['Identities']: null;
        $isAnEmailValidated = in_array($email, $validatedEmails);

        if (!$isAnEmailValidated) {
            $this->validateSendEmail($email);
        }
    }
}
