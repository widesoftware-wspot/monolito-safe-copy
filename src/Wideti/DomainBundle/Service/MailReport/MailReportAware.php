<?php
namespace Wideti\DomainBundle\Service\MailReport;

use Wideti\DomainBundle\Service\MailReport\MailReportService;

/**
 *
 * Usage: - [ setMailReportService, ["@core.service.mail_report"] ]
 */
trait MailReportAware
{
    /**
     * @var MailReportService
     */
    protected $mailReportService;

    public function setMailReportService(MailReportService $mailReportService)
    {
        $this->mailReportService = $mailReportService;
    }
}
