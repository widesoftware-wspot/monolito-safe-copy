<?php

namespace Wideti\DomainBundle\Service\Trials;

use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\WhiteLabel\WhiteLabelService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Entity\Client;

class InactivateExpiredTrials
{
    use EntityManagerAware;
    use TwigAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;

	/**
	 * @var WhiteLabelService
	 */
	private $whiteLabelService;

	/**
	 * InactivateExpiredTrials constructor.
	 * @param WhiteLabelService $whiteLabelService
	 */
	public function __construct(WhiteLabelService $whiteLabelService)
	{
		$this->whiteLabelService = $whiteLabelService;
	}

	public function execute()
    {
        $conn          = $this->em->getConnection();
        $expiredTrials = $this->listExpiredTrials($conn);

        $this->inactivateExpiredTrials($conn);
        $this->sendReport($expiredTrials);

        return;
    }

    /**
     * @param array $expiredTrials
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function sendReport(array $expiredTrials)
    {
        $template = $this->render(
            'DomainBundle:MailReport:expiredTrials.html.twig',
            [
                'entities'      => $expiredTrials,
                'whiteLabel'    => $this->whiteLabelService->getDefaultWhiteLabel()
            ]
        );

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject("[Trials] Lista de Pocs encerradas")
            ->from(["Mambo WiFi" => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getCommercialRecipient())
            ->htmlMessage($template->getContent())
            ->build()
        ;

        $this->mailerService->send($message);
    }

    /**
     * @param $connection
     * @return mixed
     */
    public function listExpiredTrials($connection)
    {
        $status_poc = Client::STATUS_POC;
        $statement  = $connection->prepare("
            SELECT 	company, 
                    domain,
		            created, 
		            poc_end_date
            FROM clients
            WHERE status = :status
            AND NOW() > DATE_ADD(poc_end_date, INTERVAL 10 DAY) 
        ");

        $statement->bindParam('status', $status_poc, \PDO::PARAM_INT);

        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Inactivate Expired Trials
     * Expired Trials are trials that has
     * @param $connection
     * @return mixed
     */
    public function inactivateExpiredTrials($connection)
    {
        $statusInactive = Client::STATUS_INACTIVE;
        $statusPoc     = Client::STATUS_POC;

        $statement = $connection->prepare("
            UPDATE 	clients
            SET		status = :status_inactive
            WHERE   status = :status_poc
            AND NOW() > DATE_ADD(poc_end_date, INTERVAL 10 DAY)
        ");

        $statement->bindParam('status_inactive', $statusInactive, \PDO::PARAM_INT);
        $statement->bindParam('status_poc', $statusPoc, \PDO::PARAM_INT);
        $statement->execute();

        return true;
    }
}
