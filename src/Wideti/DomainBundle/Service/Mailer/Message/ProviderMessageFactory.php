<?php

namespace Wideti\DomainBundle\Service\Mailer\Message;

use stdClass;

class ProviderMessageFactory
{
    /**
     * @param MailMessage $message
     * @return array
     *
     * Especificação da mensagem e outras opções:
     * https://mandrillapp.com/api/docs/messages.php.html#method-send
     */
    public static function createMandrillMessage(MailMessage $message)
    {
        $content = [
            'html'          => $message->getHtmlMessage(),
            'text'          => $message->getPlainTextMessage(),
            'subject'       => $message->getSubject(),
            'from_email'    => $message->getFrom()['email'],
            'from_name'     => $message->getFrom()['name'],
            'to'            => $message->getTo(),
            'headers' => [
                'Reply-To' => $message->getReplyTo()
            ]
        ];

        return $content;
    }

    /**
     * @param MailMessage $message
     * @return array|MailMessage
     *
     * Especificação da mensagem e outras opções:
     * http://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Ses.SesClient.html#_sendEmail
     * Foi implementado no SES e envio via RawMessage pois só assim funcionou o anexo de arquivo.
     */
    public static function createSesMessage(MailMessage $message)
    {
    	$toArray = [];

        $boundary = sha1(rand() . time() . 'WSpot');

        foreach ($message->getTo() as $to) {
            array_push($toArray, $to['name'] . ' <' . $to['email'] . '>');
        }

        $messageContent = ($message->getHtmlMessage()) ? $message->getHtmlMessage() : $message->getPlainTextMessage();

        $body = "To: ". implode(", ", $toArray) ."\n";
        $body .= "From: =?UTF-8?B?". base64_encode($message->getFrom()['name']) . '?= <' .$message->getFrom()['email']. '>' ."\n";
        $body .= "Subject: =?UTF-8?B?". base64_encode($message->getSubject()) ."?=\n";

        if ($message->getReplyTo()) {
            $body .= "Reply-To: ". $message->getReplyTo() ."\n";
        }

        if ($message->getConfigurationSet()) {
	        $body .= "X-SES-CONFIGURATION-SET: {$message->getConfigurationSet()}";
	        $body .= "\n";
	        $body .= "Identifier: {$message->getIdentifier()}";
	        $body .= "\n";
        }

	    $body .= 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
	    $body .= "\n";
        $body .= "MIME-Version: 1.0";
        $body .= "\n\n";
	    $body .= "--".$boundary;
        $body .= "\n";
        $body .= 'Content-Type: text/html; charset="utf-8"';
        $body .= "\n\n";
        $body .= $messageContent;
        $body .= "\n\n";

        if (!empty($message->getAttachment())) {
            $attachedFile = $message->getAttachment();

            $body .= "--".$boundary;
            $body .= "\n";
            $body .= 'Content-Type: '.$attachedFile["type"].'; name="'.$attachedFile["name"].'"';
            $body .= "\n";
            $body .= 'Content-Disposition: attachment; filename="'.$attachedFile["name"].'"';
            $body .= "\n";
            $body .= "Content-Transfer-Encoding: base64";
            $body .= "\n\n";
            $body .= $attachedFile['content'];
            $body .= "\n";
        }

        $body .= "--".$boundary;

        $content = [
            'ConfigurationSetName' => 'EmailMetrics',
            'RawMessage' => [
                'Data' => $body
            ]
        ];

        return $content;
    }

    /**
     * @param MailMessage $message
     * @return array
     *
     * Utilizaremos o Swift_Mailer apenas para envio de e-mail em ambiente de desenvolvimento
     */
    public static function createSwiftMailerMessage(MailMessage $message)
    {
        $content = [
            'html'          => $message->getHtmlMessage(),
            'text'          => $message->getPlainTextMessage(),
            'subject'       => $message->getSubject(),
            'from_email'    => $message->getFrom()['email'],
            'from_name'     => $message->getFrom()['name'],
            'to'            => $message->getTo(),
            'headers' => [
                'Reply-To' => $message->getReplyTo()
            ]
        ];

        return $content;
    }
}
