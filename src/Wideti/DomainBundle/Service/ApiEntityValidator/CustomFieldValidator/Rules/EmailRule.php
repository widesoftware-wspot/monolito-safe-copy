<?php
/**
 * Created by PhpStorm.
 * User: romani
 * Date: 29/05/17
 * Time: 14:43
 */

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;


class EmailRule implements Rule
{
    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br")
    {
        return $this->isValid($entityValue);
    }

    private function isValid($email)
    {
        $rejectDomain = array(
            'emailtemporario.com.br', 'getairmail.com', 'guerrillamail.com', 'meltmail.com',
            '10minutemail.com', 'jetable.org', 'spammotel.com', 'mailexpire.com', 'mytrashmail.com',
            'mailinator.com', 'temp-mail.org', 'yopmail.com', 'mintemail.com', 'tempemail.net',
            'maildrop.cc', 'tempail.com', 'fakemailgenerator.com', 'tempsky.com', 'fakeinbox.com',
            'sharklasers.com', 'dropmail.me', 'dispostable.com', 'mailnesia.com', '20minutemail.com',
            'deadaddress.com', 'emailsensei.com', 'filzmail.com', 'incognitomail.com', 'koszmail.pl',
            'mailcatch.com', 'noclickemail.com', 'spamfree24.org', 'trashmail.ws', 'stop-my-spam.com', 'gmail'
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domainEmail = explode("@", $email);

        if (in_array($domainEmail[1], $rejectDomain)) {
            return false;
        }

        return true;
    }
}