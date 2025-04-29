<?php

namespace Wideti\DomainBundle\Validator;

class DomainEmailValidate
{
    private $email;

    private function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function validate($email)
    {
        if (!$email || strpos($email, "@") === false) {
            return true;
        }

        $this->setEmail($email);

        $rejectDomain = [
            'emailtemporario.com.br', 'getairmail.com', 'guerrillamail.com', 'meltmail.com',
            '10minutemail.com', 'jetable.org', 'spammotel.com', 'mailexpire.com', 'mytrashmail.com',
            'mailinator.com', 'temp-mail.org', 'yopmail.com', 'mintemail.com', 'tempemail.net',
            'maildrop.cc', 'tempail.com', 'fakemailgenerator.com', 'tempsky.com', 'fakeinbox.com',
            'sharklasers.com', 'dropmail.me', 'dispostable.com', 'mailnesia.com', '20minutemail.com',
            'deadaddress.com', 'emailsensei.com', 'filzmail.com', 'incognitomail.com', 'koszmail.pl',
            'mailcatch.com', 'noclickemail.com', 'spamfree24.org', 'trashmail.ws', 'stop-my-spam.com', 'gmail'
        ];

        $domainEmail = explode("@", $email);

        if (in_array($domainEmail[1], $rejectDomain)) {
            return false;
        }

        return true;
    }
}
