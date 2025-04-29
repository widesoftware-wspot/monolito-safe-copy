<?php

namespace Wideti\DomainBundle\Service\CustomFields;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Document\CustomFields\CustomFieldTemplate;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Exception\NonUniqueFieldOnGuestsException;
use Wideti\DomainBundle\Exception\ClientPlanNotFoundException;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\Dto\FieldsToLoginDto;
use Wideti\DomainBundle\Service\CustomFields\Dto\FieldsViewDto;
use Wideti\DomainBundle\Service\CustomFields\Helper\CustomFieldMapper;
use Wideti\DomainBundle\Service\Plan\PlanServiceImp;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Document\Repository\Fields\FieldRepository;

class CustomFieldsService
{
    use EntityManagerAware;
    use MongoAware;
    use SessionAware;
    use SecurityAware;
    use TwigAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $customFieldsCacheService;
    /**
     * @var GuestRepository
     */
    private $guestRepository;
    /**
     * @var FieldRepository
     */
    private $customFieldsRepository;
    /**
     * @var PlanServiceImp
     */
    private $planService;

    /**
     * CustomFieldsService constructor.
     * @param ConfigurationService $configurationService
     * @param CustomFieldsCacheService $customFieldsCacheService
     * @param GuestRepository $guestRepository
     * @param FieldRepository $customFieldsRepository
     * @param PlanServiceImp $planService
     */
    public function __construct(
        ConfigurationService $configurationService,
        CustomFieldsCacheService $customFieldsCacheService,
        GuestRepository $guestRepository,
        FieldRepository $customFieldsRepository,
        PlanServiceImp $planService
    ) {
        $this->configurationService     = $configurationService;
        $this->customFieldsCacheService = $customFieldsCacheService;
        $this->guestRepository          = $guestRepository;
        $this->customFieldsRepository   = $customFieldsRepository;
        $this->planService              = $planService;
    }

    /**
     * @return array|\Wideti\DomainBundle\Document\CustomFields\Field[]
     */
    public function getCustomFields()
    {
        $repository = $this->mongo->getRepository('DomainBundle:CustomFields\Field');
        return $repository->findAll();
    }

    /**
     * @param $name
     * @return Field
     */
    public function getFieldByNameType($name)
    {
        $repository = $this->mongo->getRepository('DomainBundle:CustomFields\Field');
        return $repository->findOneBy(['identifier' => $name]);
    }

    /**
     * @return Field[]
     * @throws \Exception
     */
    public function getLoginField()
    {
        $repository = $this->mongo->getRepository('DomainBundle:CustomFields\Field');
        $result     = $repository->findBy(['isLogin' => true]);

        if (count($result) > 1) {
            throw new \Exception('More than one login field was found');
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getLoginFieldIdentifier()
    {
        $repository = $this->mongo->getRepository('DomainBundle:CustomFields\Field');
        $result     = $repository->findOneBy(['isLogin' => true]);
        return $result->getIdentifier();
    }

    /**
     * @param Field $field
     * @return bool
     */
    public function isRequired(Field $field)
    {
        $validations = $field->getValidations();

        if (!empty($validations)) {
            foreach ($validations as $rule) {
                if ($rule['type'] == 'required') {
                    return $rule['value'];
                }
            }
        }

        return false;
    }

    /**
     * @param CustomFieldTemplate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($template) {
        $this->em->remove($template);
        $this->em->flush();
    }

    public function getOnlyAvailableTemplates()
    {
        $client = $this->getLoggedClient();

        $allTemplates = $this->em
            ->getRepository('DomainBundle:CustomFieldTemplate')
            ->getAllFieldsAvailableToClient($client);

        $clientFields = $this->mongo
           ->getRepository('DomainBundle:CustomFields\Field')
           ->findAll();

        $templates = (array) array_filter($allTemplates, function ($template) use ($clientFields) {
            /**
             * @var CustomFieldTemplate $template
             */
            $hasInClient = false;
            foreach ($clientFields as $cField) {
                if ($template->getIdentifier() === $cField->getIdentifier()) {
                    $hasInClient = true;
                }
            }
            return !$hasInClient;
        });

        $templates = array_values($templates);
        $allTemplates = array_values($allTemplates);
        return new FieldsViewDto($templates, $clientFields, $allTemplates);
    }

    /**
     * @param Client $client
     * @return array
     * @throws ClientPlanNotFoundException
     */
    public function getTemplateFields(Client $client)
    {
        if (!$client->getPlan()) {
            throw new ClientPlanNotFoundException();
        }

        if ($client->getPlan()->getShortCode() == Plan::BASIC) {
            return $this->em->getRepository('DomainBundle:CustomFieldTemplate')
                ->findBy([
                    'identifier' => [
                        'name', 'email'
                    ]
                ]);
        }

        return $this->em
            ->getRepository('DomainBundle:CustomFieldTemplate')
            ->getAllFieldsAvailableToClient($client);
    }

    /**
     * @param array | Field $fields
     * @param Client $client
     */
    public function saveAll(array $fields, Client $client)
    {
        $this->customFieldsRepository->saveAll($fields, $client);

        $this->disableModules($client);

        $this->customFieldsCacheService->removeByKey(
            'wspot:' . $client->getDomain() . ':' . CacheServiceImp::CUSTOM_FIELDS
        );
    }

    /**
     * @return FieldsToLoginDto
     */
    public function getFieldsToLogin()
    {
        $client = $this->getLoggedClient();
        $captiveType = $client->getAuthenticationType();
        if ($captiveType == "disable_password_authentication") {
            $fields = $this->getAllFields();
            $fields = $this->removeNotRequiredFields($fields);
        } else {
            $fields = $this->getUniqueFields();
            $fields = $this->removeNotRequiredFields($fields);
        }
        $filteredFields = array_filter($fields, function ($field) {
            return $field->getOnAccess() == 1 || $field->getOnAccess() == null;
        });

        return new FieldsToLoginDto($filteredFields);
    }

    private function removeNotRequiredFields(array $fields)
    {
        $requireds = [];
        /**
         * @var Field $field
         */
        foreach ($fields as $index => $field) {
            $validations = $field->getValidations();
            foreach ($validations as $valid) {
                if ($valid['type'] === 'required' && $valid['value'] == true) {
                    $requireds[] = $field;
                }
            }
        }
        return $requireds;
    }
    public function getAllFields()
    {

        $fields = $this->mongo
                ->getRepository('DomainBundle:CustomFields\Field')
                ->findAll();

            return $fields;
        }

    public function setFieldToLogin($identifier)
    {
        $field = $this->getFieldByNameType($identifier);

        if (!$this->guestRepository->checkExistsPropertyInAllBase($identifier)) {
            throw new NonUniqueFieldOnGuestsException(
                "Não é possível usar o campo " . strtolower($field->getName()["pt_br"]) . " como login pois esse campo não existe em toda a base de visitantes, contate o suporte para limpar a base"
            );
        }
        if($this->guestRepository->checkDuplicatePropertyInAllBase($identifier)) {
            throw new NonUniqueFieldOnGuestsException(
                "Não é possível usar o campo " . strtolower($field->getName()["pt_br"]) . " como login pois existem dados duplicados na base de visitantes, contate o suporte para limpar a base"
            );
        }

        $repository = $this->mongo->getRepository('DomainBundle:CustomFields\Field');
        $loginField = $repository->findOneBy(['isLogin' => true]);
        $client = $this->getLoggedClient();
        $captiveType = $client->getAuthenticationType();

        if ($loginField) {
            $loginField->setIsLogin(false);
            if ($captiveType == "disable_password_authentication") {
                $loginField->setIsUnique(false);}
            $this->mongo->persist($loginField);
        }
        $newLoginField = $repository->findOneBy(['identifier' => $identifier]);
        $newLoginField->setIsLogin(true);
        $newLoginField->setIsUnique(true);
        $newLoginField->setOnAccess(1);
        $this->mongo->persist($newLoginField);

        $this->mongo->flush();

        $this->customFieldsCacheService->removeByKey(
            'wspot:' . $this->getLoggedClient()->getDomain() . ':' . CacheServiceImp::LOGIN_FIELD
        );
    }

    /**
     * @return array|Field[]
     */
    public function getUniqueFields()
    {
        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findBy([
                'isUnique' => true
            ]);

        return $fields;
    }

    /**
     * @param $identifier
     * @return object|\Wideti\DomainBundle\Entity\CustomFieldTemplate|null
     */
    public function getTemplateFieldByIdentifier($identifier)
    {
        return $this->em->getRepository('DomainBundle:CustomFieldTemplate')
            ->findOneBy([
                'identifier' => $identifier
            ]);
    }

    /**
     * @param $type
     */
    public function getTemplateFieldByType($type)
    {
        return $this->em->getRepository('DomainBundle:CustomFieldTemplate')
            ->findBy([
                'type' => $type
            ]);
    }

    public function checkAndCreateRequiredFields($fieldsIdentifiers, $client) {
        $fieldsToCreate = [];
        $existingFiedls = $this->getCustomFields();
        $hasChanges = false;

        foreach ($fieldsIdentifiers as $identifier) {
            $exists = false;
            foreach ($existingFiedls as $field) {
                if ($field->getIdentifier() == $identifier) {
                    $exists = true;
                    $required = $this->isRequired($field);
                    $isFirstAccess = $field->getOnAccess() == 1 || $field->getOnAccess() == null;
                    if (!$required) {
                        $this->setRequiredValidation($field, true);
                        $hasChanges = true;
                        $this->mongo->persist($field);
                    }
                    if (!$isFirstAccess) {
                        $field->setOnAccess(1);
                        $hasChanges = true;
                        $this->mongo->persist($field);
                    }
                }
            }
            
            if (!$exists) {
                $fieldsToCreate[] = $identifier;
            }
        }

        if ($fieldsToCreate) {
            $hasChanges = true;
            foreach ($fieldsToCreate as $identifier) {
                $fieldTemplate = $this->getTemplateFieldByIdentifier($identifier);
                $fieldToCreate = new Field();
                $fieldToCreate->setType($fieldTemplate->getType());
                $fieldToCreate->setName($fieldTemplate->getName());
                $fieldToCreate->setMask($fieldTemplate->getMask());
                    
                $fieldToCreate->setIdentifier($identifier);
                $fieldToCreate->setValidations([
                        [
                            'type' => 'required',
                            'value' => true,
                            'message' => 'wspot.signup_page.field_required',
                            'locale' => ['pt_br', 'en', 'es'],
                        ]]);
                $fieldToCreate->setPosition(20);
                $this->mongo->persist($fieldToCreate);
            }
        }

        if ($hasChanges) {
            $this->mongo->flush();
            $this->customFieldsCacheService->removeByKey(
                'wspot:' . $client->getDomain() . ':' . CacheServiceImp::CUSTOM_FIELDS
            );
        }
    }

    /**
     * @param Field $field
     * @param $isRequired
     * @return Field
     */
    public function setRequiredValidation(Field $field, $isRequired)
    {
        $validations = $field->getValidations();
        for ($i = 0; $i < count($validations); $i++) {
            if ($validations[$i]['type'] === 'required') {
                $validations[$i]['value'] = $isRequired;
            }
        }
        $field->setValidations($validations);
        return $field;
    }

    /**
     * @param Client $client
     */
    private function disableModules(Client $client)
    {
        $emailField = $this->getFieldByNameType('email');
        $phoneField = $this->getFieldByNameType('email');

        if (!$emailField) {
            $this->configurationService->updateKey('authorize_email', 0, $client);
            $this->configurationService->updateKey('enable_confirmation', 0, $client);
            $this->configurationService->updateKey('confirmation_email', 0, $client);
        }

        if (!$phoneField) {
            $this->configurationService->updateKey('enable_welcome_sms', 0, $client);
            $this->configurationService->updateKey('enable_confirmation', 0, $client);
            $this->configurationService->updateKey('confirmation_sms', 0, $client);
        }
    }

    /**
     * @param Request $request
     * @return array|Field
     */
    public function parseArrayToObjectField(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (gettype($data) != "array") {
            throw new \InvalidArgumentException("Invalid post data format");
        }

        return CustomFieldMapper::arrayToObjectList($data);
    }

    private function removeEmptyValues($array) {
        return array_filter($array, function($value) {
            return $value !== "";
        });
    }

    private function mergeChoices($saved, $placeholder, $new) {
        $merged = [];
    
        foreach ($saved as $lang => $choices) {
            $merged[$lang] = array_merge(
                $choices,
                isset($placeholder[$lang]) ? $placeholder[$lang] : [],
                isset($new[$lang]) ? $new[$lang] : []
            );
        }
    
        return $merged;
    }

    public function updateCustomFieldFromForm($customField, $data) {
        $customFieldNames = [
            'pt_br' => $data["labelPt"],
            'en' => $data["labelEn"],
            'es' => $data["labelEs"]
        ];
        $customField->setNames($customFieldNames);

        $choicesToCreate = null;
        if ($customField->getType() == "choice") {
            $choices = $data["choices"];
            $savedChoices = $customField->getChoices();         
            foreach ($savedChoices as $language => $choicesByLanguage) {
                $savedChoices[$language] = $this->removeEmptyValues($choicesByLanguage);
            }
            $placeholderChoice = $this->createDataFromChoices(["0" => ["isSaved"=>"false","isLabel"=>"true","pt_br"=>$data["labelPt"],"es"=>$data["labelEs"],"en"=>$data["labelEn"]]]);
            $choices = array_filter($choices, function($choice) {
                return $choice["isSaved"] == "false";
            });
            $choicesToCreate = $this->createDataFromChoices($choices);
            $allChoices = $this->mergeChoices($placeholderChoice, $savedChoices, $choicesToCreate);
            $customField->setChoices($allChoices);
        }
        if ($customField->getType() == "multiple_choice") {
            $choices = $data["choices"];
            $choices = array_filter($choices, function($choice) {
                return $choice["isSaved"] == "false";
            });
            $choicesToCreate = $this->createDataFromChoices($choices);
            $allChoices = [];
            $savedChoices = $customField->getChoices();

            foreach ($savedChoices as $lang => $choices) {
                $allChoices[$lang] = array_merge(
                    $choices,
                    isset($choicesToCreate[$lang]) ? $choicesToCreate[$lang] : []
                );
            }
            $customField->setChoices($allChoices);
        }

        $this->em->persist($customField);
        $this->em->flush();
        return $customField;
    }

    public function createCustomFieldFromForm($customField, $data) {
        $customFieldNames = [
            'pt_br' => $data["labelPt"],
            'en' => $data["labelEn"],
            'es' => $data["labelEs"]
        ];
        $customField->setNames($customFieldNames);
        $customField->setVisibleForClients([$data["clientDomain"]]);
        $domainIdentifier = str_replace('.', '_', $data["clientDomain"]);

        $fieldName = strtolower($customFieldNames["pt_br"]);
        $acentos = array(
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c'
        );

        $fieldName = strtr($fieldName, $acentos);
        $fieldName = preg_replace('/[^a-z_]/', '', str_replace(" ", "_", $fieldName));

        $customField->setIdentifier("custom_" . $domainIdentifier . "_" . $fieldName);


        $validations = [
            [
                'type' => 'required',
                'value' => true,
                'message' => 'wspot.signup_page.field_required',
                'locale' => ['pt_br','en','es']
            ]
        ];
        $customField->setValidations($validations);
        $choicesToCreate = null;
        if ($customField->getType() == "date") {
            $customField->setMask(
                [
                "pt_br"=>"99/99/9999",
                "en"=>"99/99/9999",
                "es"=>"99/99/9999"
                ]
            );
        }
        if ($customField->getType() == "choice") {
            $choices = $data["choices"];
            $newChoice = ["0" => ["isSaved"=>"false","isLabel"=>"true","pt_br"=>$data["labelPt"],"es"=>$data["labelEs"],"en"=>$data["labelEn"]]];
            $choices = array_merge($newChoice, $choices);

            $choicesToCreate = $this->createDataFromChoices($choices);
            $customField->setChoices($choicesToCreate);
        }
        if ($customField->getType() == "multiple_choice") {
            $choices = $data["choices"];
            $choicesToCreate = $this->createDataFromChoices($choices);
            $customField->setChoices($choicesToCreate);
        }

        $this->em->persist($customField);
        $this->em->flush();
        return $customField;
    }

    private function getChoiceData($choice, $language) {
        if ($choice["isLabel"] == "true") {
            return "";
        }
        return $choice[$language];
    }

    function createDataFromChoices($choices) {
        $data = [
            'pt_br' => [],
            'en' => [],
            'es' => []
        ];
    
        foreach ($choices as $choice) {
            if (isset($choice['pt_br'])) {
                $data['pt_br'][$choice['pt_br']] = $this->getChoiceData($choice, 'pt_br');
            }
            if (isset($choice['en'])) {
                $data['en'][$choice['en']] = $this->getChoiceData($choice, 'en');
            }
            if (isset($choice['es'])) {
                $data['es'][$choice['es']] = $this->getChoiceData($choice, 'es');
            }
        }
    
        return $data;
    }

    /**
     * @param Request $request
     * @param Client $client
     * @return JsonResponse
     */
    public function ajaxSaveFields(Request $request, Client $client)
    {
        try {
            $this->saveAll($this->parseArrayToObjectField($request), $client);
            $this->customFieldsCacheService->clear();

            return new JsonResponse([
                "message" => "Configuração salva com sucesso!"
            ]);
        } catch (\MongoDuplicateKeyException $e) {
            return new JsonResponse([
                "message" => "Você definiu o campo como único, porém já há cadastros com valores duplicados!"
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                "message" => "Ocorreu um erro, tente novamente mais tarde."
            ], 500);
        }
    }

    public function checkIfIsPhoneOrMobileCustomField()
    {
        $phone = $this->getFieldByNameType("phone");
        if ($phone) return "phone";

        $mobile = $this->getFieldByNameType("mobile");
        if ($mobile) return "mobile";

        return null;
    }

    /**
     * Salva o field especifico de validação de idade e
     * respectivo groupId do ponto de acesso cuja regra esta ativa
     *
     * @param string $identifier
     * @param int $groupId
     */
    public function saveAgeRestrictionField($groupId, $identifier = 'age_restriction')
    {
        $existingField = $this->customFieldsRepository->findOneBy(['identifier' => $identifier]);
        $client = $this->getLoggedClient();

        if ($existingField) {
            $currentGroupId = $existingField->getGroupId();
            $currentGroupId[] = $groupId;
            $currentGroupId = array_unique($currentGroupId);
            $existingField->setGroupId($currentGroupId);
            $this->mongo->persist($existingField);
            $this->mongo->flush();
        } else {
            $field = new Field();
            $field->setType('date');
            $field->setName([
                'pt_br' => 'Data de Nascimento',
                'en' => 'Birth date',
                'es' => 'Fecha de nacimiento',
            ]);
            $field->setIdentifier('age_restriction');
            $field->setChoices([]);
            $field->setValidations([
                [
                    'type' => 'required',
                    'value' => true,
                    'message' => 'wspot.signup_page.field_required',
                    'locale' => ['pt_br', 'en', 'es'],
                ],
                [
                    'type' => 'customDate',
                    'value' => ['required' => true],
                    'message' => 'wspot.signup_page.field_invalid_date',
                    'locale' => ['pt_br', 'en', 'es'],
                ],
                [
                    'type' => 'ageRestriction',
                    'value' => ['required' => true],
                    'message' => 'wspot.signup_page.field_age_restriciton_error',
                    'locale' => ['pt_br', 'en', 'es'],
                ],
            ]);
            $field->setMask([
                'pt_br' => '99/99/9999',
                'en' => '99/99/9999',
                'es' => '99/99/9999',
            ]);
            $field->setIsUnique(false);
            $field->setIsLogin(false);
            $field->setPosition(20);


            $field->setGroupId([$groupId]);

            $this->mongo->persist($field);
            $this->mongo->flush();
        }

        $this->customFieldsCacheService->removeByKey(
            'wspot:' . $client->getDomain() . ':' . CacheServiceImp::CUSTOM_FIELDS
        );
    }

        /**
     * Remove um groupId da lista e
     * apaga o campo se a lista ficar vazia
     *
     * @param string|CustomFieldTemplate $identifier
     */
    public function removeGroupIdFromField($identifier, $groupIdToRemove)
    {
        $existingField = $this->customFieldsRepository->findOneBy(['identifier' => $identifier]);
        $client = $this->getLoggedClient();

        if ($existingField) {
            $groupIds = $existingField->getGroupId();
            $groupIds = (array_diff($groupIds, [$groupIdToRemove]));
            $existingField->setGroupId($groupIds);

            if (empty($groupIds)) {
                $this->mongo->remove($existingField);
            } else {
                $this->mongo->persist($existingField);
            }

            $this->mongo->flush();
            $this->customFieldsCacheService->removeByKey(
            'wspot:' . $client->getDomain() . ':' . CacheServiceImp::CUSTOM_FIELDS
        );
        }
    }
}