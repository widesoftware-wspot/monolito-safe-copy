<?php

namespace Wideti\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Helpers\FieldsHelper;
use Wideti\DomainBundle\Service\NasManager\NasService;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\FrontendBundle\Form\ProgressiveFormType;

class ProgressiveFormController implements NasControllerHandler
{
    use TwigAware;
    use MongoAware;
    use SessionAware;
    use TemplateAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;

    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * @var NasService
     */
    private $nasService;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var GuestService
     */
    private $guestService;

    public function __construct(
        FrontendControllerHelper $controllerHelper, 
        NasService $nasService, 
        GuestService $guestService, 
        LegalBaseManagerService $legalBaseManagerService,
        CacheServiceImp $cacheService
    )
    {
        $this->controllerHelper      = $controllerHelper;
        $this->nasService            = $nasService;
        $this->guestService          = $guestService;
        $this->legalBaseManager         = $legalBaseManagerService;
        $this->cacheService = $cacheService;
    }

    public function indexAction(Request $request)
    { 
        /** @var Nas $sessionNas */
        $sessionNas = $this->session->get(Nas::NAS_SESSION_KEY);
        $guest_id = $this->session->get('guest_id');
        $guest = $this->guestService->getGuestById($guest_id);
        $countVisits = $guest->getCountVisits();
        $client = $this->getLoggedClient();
        $fields = $this->getCustomFieldsCache($countVisits, $guest, $client->getAskRetroactiveGuestFields());

        if (count($fields) == 0) {
            return $this->nasService->process($guest, $sessionNas, true, false);
        }

        $template = $this->templateService->templateSettings($this->session->get('campaignId'));
        $guest->setLocale($request->getLocale());
        $multipleChoiceFields = [];
        foreach ($fields as $field) {
            if ($field->getType() == "multiple_choice") {
                $multipleChoiceFields[] = $field->getIdentifier();
            }
        }
        $this->formatMultipleChoiceEmptyFields($guest, $multipleChoiceFields);

        $form = $this->controllerHelper->createForm(
            ProgressiveFormType::class,
            $guest,
            [
                'action'            => $this->controllerHelper->generateUrl('frontend_progressive_form'),
                'fields'            => $fields,
                'method'            => 'POST'
            ]
        );

        $form->handleRequest($request);

        if ($request->getMethod() == "POST"){
            FieldsHelper::transformPhoneAndMobileGuest($guest, $form);
            $this->formatMultipleChoiceFields($guest, $multipleChoiceFields);
            return $this->nasService->process($guest, $sessionNas, true, false);
        }

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);

        return $this->render(
            'FrontendBundle:SignIn:progressiveForm.html.twig',
            [
                'template'          => $template,
                'data'              => $guest,
                'form'              => $form->createView(),
                'bounceValidator'   => 1,
                'requiredOptIn'     => false,
                'activeLegalBase'   => $activeLegalBase
            ]
        );
    }

    private function formatMultipleChoiceEmptyFields($guest, $multipleChoiceFields) {
        if (!$multipleChoiceFields) {
          return ;
        }
        $properties = $guest->getProperties();
        foreach ($multipleChoiceFields as $multipleChoiceField) {
            if (isset($properties[$multipleChoiceField]) && !$properties[$multipleChoiceField]) {
                $properties[$multipleChoiceField] = [];
            }
        }
        $guest->setProperties($properties);
    }

    private function formatMultipleChoiceFields($guest, $multipleChoiceFields) {
        if (!$multipleChoiceFields) {
          return ;
        }
        $properties = $guest->getProperties();
        foreach ($multipleChoiceFields as $multipleChoiceField) {
            $properties[$multipleChoiceField] = implode(' - ', $properties[$multipleChoiceField]);
        }
        $guest->setProperties($properties);
    }

    /**
     * @return mixed
     */
    private function getCustomFields()
    {
        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields();

        $fields = $fields->toArray();

        return $fields;
    }

    /**
     * @return mixed
     */
    private function getCustomFieldsCache($countVisits, $guest, $askRetroactive = false)
    {
        try {
            if (!$this->cacheService->isActive()) {
                $fields = $this->getCustomFields();
            } elseif ($this->cacheService->exists(CacheServiceImp::CUSTOM_FIELDS) !== 1) {
                $fields = $this->getCustomFields();
                $this->cacheService->set(CacheServiceImp::CUSTOM_FIELDS, $fields, CacheServiceImp::TTL_CUSTOM_FIELDS);
            } else {
                $fields = $this->cacheService->get(CacheServiceImp::CUSTOM_FIELDS);
            }
        } catch (\Exception $e) {
            $fields = $this->getCustomFields();
        }

        if ($countVisits < 4) {
            $properties = $guest->getProperties();
            $propertyKeys = array_keys($properties);
            $filteredFields = array_filter($fields, function ($field) use ($propertyKeys, $countVisits, $properties, $askRetroactive) {
                $isRetroactiveField = $field->getOnAccess() == 1 || $field->getOnAccess() < $countVisits || !$field->getOnAccess();
                if (
                    $askRetroactive
                    && $isRetroactiveField
                    && in_array($field->getIdentifier(), $propertyKeys) 
                    && $properties[$field->getIdentifier()] == null 
                    && !$field->getIsLogin()
                    && $field->getIdentifier() != "age_restriction"
                ) {
                    $validations = $field->getValidations();
                    $requiredValidation = [
                        "value" => false
                    ];

                    foreach ($validations as $validation) {
                        if ($validation['type'] === 'required') {
                            $requiredValidation = $validation;
                            break;
                        }
                    }
                    return $requiredValidation["value"];
                } else if (
                    $askRetroactive
                    && $isRetroactiveField
                    && !in_array($field->getIdentifier(), $propertyKeys) 
                    && !$field->getIsLogin()
                    && $field->getIdentifier() != "age_restriction"
                ) {
                    return true;
                }          
                return (
                    !in_array($field->getIdentifier(), $propertyKeys) 
                    && $field->getOnAccess() == $countVisits 
                    && !$field->getIsLogin()
                    && $field->getIdentifier() != "age_restriction"
                );
            });
            return $filteredFields;
        } elseif ($countVisits > 3) {
            $properties = $guest->getProperties();
            $propertyKeys = array_keys($properties);
            $filteredFields = array_filter($fields, function ($field) use ($propertyKeys, $properties) {
                if (
                    in_array($field->getIdentifier(), $propertyKeys) 
                    && $properties[$field->getIdentifier()] == null 
                    && !$field->getIsLogin()
                    && $field->getIdentifier() != "age_restriction"
                ) {
                    $validations = $field->getValidations();
                    $requiredValidation = [
                        "value" => false
                    ];

                    foreach ($validations as $validation) {
                        if ($validation['type'] === 'required') {
                            $requiredValidation = $validation;
                            break;
                        }
                    }
                    return $requiredValidation["value"];
                } elseif(in_array($field->getIdentifier(), $propertyKeys) || $field->getIdentifier() == "age_restriction") {
                    return false;
                }
                return true;
            });

            return $filteredFields;
        }
        return [];
    }
}