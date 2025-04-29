<?php

namespace Wideti\DomainBundle\Service\FirstConfig;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\CustomFieldTemplate;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FieldFirstConfigDTO;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FirstConfigResponse;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FirstConfigurationParameters;

class FirstConfigServiceImp implements FirstConfigService
{
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    /**
     * @var ClientService
     */
    private $clientService;

    public function __construct(CustomFieldsService $customFieldsService, ClientService $clientService)
    {
        $this->customFieldsService = $customFieldsService;
        $this->clientService = $clientService;
    }

	/**
	 * @param Client $client
	 * @return array|FieldFirstConfigDTO
	 * @throws \Wideti\DomainBundle\Exception\ClientPlanNotFoundException
	 */
    function getTemplateFields(Client $client)
    {
        $templateFields = $this
            ->customFieldsService
            ->getTemplateFields($client);

        $fieldsDto = array_map(function (CustomFieldTemplate $template) {
            $fieldDto = new FieldFirstConfigDTO();
            $fieldDto->setIdentifier($template->getIdentifier());
            $fieldDto->setLabel($template->getName()['pt_br']);
            $fieldDto->setUnique($template->getIsUnique());
            $fieldDto->setLogin($template->getIsLogin());
            $fieldDto->setRequired($this->customFieldsService->isRequired($template->getField()));
            return $fieldDto;
        }, $templateFields);

        return $fieldsDto;
    }

	/**
	 * @param FirstConfigurationParameters $configParameters
	 * @param Client $client
	 * @return FirstConfigResponse
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function processConfigParameters(FirstConfigurationParameters $configParameters, Client $client)
    {
        $this->saveFields($configParameters, $client);
        $this->clientService->doneFirstConfiguration($client);
        return new FirstConfigResponse("Configuração salva com sucesso");
    }

    /**
     * @param Client $client
     */
    public function firstConfigurationNoRegisterFields(Client $client) {
        $configParameters = new FirstConfigurationParameters();
        $field = [
            'identifier' => 'mac_address',
            'login' => true,
            'unique' => true,
            'required' => false,
            'label' => true
        ];

        $signUpFields = [FieldFirstConfigDTO::buildFromArray($field)];

        $configParameters->setSignUpFields($signUpFields);
        $configParameters->setSignInField(FieldFirstConfigDTO::buildFromArray($field));

        $this->saveFields($configParameters, $client);
        $client->setInitialSetup(true);
        $this->clientService->saveClient($client);

        return $configParameters;
    }

    /**
     * @param Client $client
     */
    public function clearFirstConfiguration(Client $client) {
        $configParameters = new FirstConfigurationParameters();

        $configParameters->setSignUpFields([]);
        $configParameters->setSignInField([]);

        $this->saveFields($configParameters, $client);
        $client->setInitialSetup(false);
        $this->clientService->saveClient($client);

        return $configParameters;
    }

    /**
     * @param FirstConfigurationParameters $configParameters
     * @param Client $client
     */
    private function saveFields(FirstConfigurationParameters $configParameters, Client $client)
    {
    	$fields = [];
        /**
         * @var FieldFirstConfigDTO $configField
         */
        foreach ($configParameters->getSignUpFields() as $configField) {
            $templateField = $this->customFieldsService->getTemplateFieldByIdentifier($configField->getIdentifier());
            $field = $templateField->getField();
	        $field->setIsLogin($configField->isLogin());
	        $field->setIsUnique($configField->isUnique());
            $field = $this->customFieldsService->setRequiredValidation($field, $configField->isRequired());
            $fields[] = $field;
        }
        $this->customFieldsService->saveAll($fields, $client);
    }
}
