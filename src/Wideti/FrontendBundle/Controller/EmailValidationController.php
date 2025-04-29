<?php
namespace Wideti\FrontendBundle\Controller;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\BouncedValidation\BouncedValidationImp;

class EmailValidationController
{
    private $bounceValidatorActive;
    /**
     * @var BouncedValidationImp
     */
    private $bouncedValidation;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * EmailValidationController constructor.
     * @param $bounceValidatorActive
     * @param BouncedValidationImp $bouncedValidation
     * @param Logger $logger
     */
    public function __construct($bounceValidatorActive, BouncedValidationImp $bouncedValidation, Logger $logger)
    {
        $this->bounceValidatorActive = $bounceValidatorActive;
        $this->bouncedValidation = $bouncedValidation;
        $this->logger = $logger;
    }

    public function validate(Request $request)
    {
        if ($request->getMethod() != 'POST' || !$request->get('email')) {
            return new JsonResponse(false, 400);
        }

        if ($this->bounceValidatorActive) {
            $email = strtolower($request->get('email'));

            if (!empty($email)) {
                try {
                    $emailValidation = $this->bouncedValidation->isValid($email);
                } catch (\Exception $e) {
                    $emailValidation = true;
                    $this->logger->addCritical("Fail to validate e-mail on ServiceBounce API: {$e->getMessage()}", [
                        'error' => $e
                    ]);
                }

                if ($emailValidation) {
                    return new JsonResponse($emailValidation, 200);
                }
            }

            return new JsonResponse(false, 400);
        }

        return new JsonResponse('');
    }
}
