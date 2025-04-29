<?php

namespace Wideti\WebFrameworkBundle\Aware;

/**
 * How to use it:
 *      - [ setMailer, [@mailer] ]
*/
trait MailerAware
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    public function setMailer(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }
}
