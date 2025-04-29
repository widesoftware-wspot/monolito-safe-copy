<?php

namespace Wideti\DomainBundle\Service\BulkInsert;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Exception\AccessPointExistsException;
use Wideti\DomainBundle\Exception\ErrorOnCreateAccessPointException;
use Wideti\DomainBundle\Exception\InvalidFileTypeException;
use Wideti\DomainBundle\Helpers\CsvHelper;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\BulkInsert\Dto\BulkResponse;
use Wideti\DomainBundle\Service\BulkInsert\Dto\ValidateField;
use Wideti\DomainBundle\Service\Template\TemplateService;
use Wideti\DomainBundle\Service\Vendor\VendorService;

class AccessPointBulkInsertService implements BulkInsertService
{
    const FIELD_FRIENDLY_NAME = 0;
    const FIELD_VENDOR = 1;
    const FIELD_IDENTIFIER = 2;
    const FIELD_AP_GROUP = 3;
    const FIELD_LOCAL = 4;
    const FIELD_TEMPLATE = 5;
    const FIELD_TIMEZONE = 6;
    const DEFAUlT_GROUP = 'Grupo padrão';

    const REQUIRED_FILE_HEADERS = [
        'Nome do ponto de acesso',
        'Fabricante',
        'Identificador',
        'Grupo',
        'Local',
        'Template',
        'Timezone'
    ];

    /**
     * @var AccessPointsService
     */
    private $accessPointsService;

    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;
    /**
     * @var TemplateService
     */
    private $templateService;

    /**
     * @var VendorService
     */
    private $vendorService;

    /**
     * @var TimezoneService
     */
    private $timezoneService;

    /**
     * AccessPointBulkInsertService constructor.
     * @param AccessPointsService $accessPointsService
     * @param AccessPointsGroupsService $accessPointsGroupsService
     * @param TemplateService $templateService
     * @param VendorService $vendorService
     * @param TimezoneService $timezoneService
     */
    public function __construct(
        AccessPointsService $accessPointsService,
        AccessPointsGroupsService $accessPointsGroupsService,
        TemplateService $templateService,
        VendorService $vendorService,
        TimezoneService $timezoneService
    ) {
        $this->accessPointsService = $accessPointsService;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->templateService = $templateService;
        $this->vendorService = $vendorService;
        $this->timezoneService = $timezoneService;
    }

    /**
     * @param UploadedFile $file
     * @param Client $client
     * @return BulkResponse
     * @throws InvalidFileTypeException
     */
    public function process(UploadedFile $file, Client $client)
    {
        $this->checkFileType($file);
        $this->checkFileHeader($file);
        return $this->processFile($file, $client);
    }

    /**
     * @param UploadedFile $file
     * @throws InvalidFileTypeException
     */
    private function checkFileType(UploadedFile $file)
    {
        if (strtolower($file->getClientOriginalExtension()) != 'csv') {
            throw new InvalidFileTypeException(
                "O formato do arquivo é inválido.<br>Permitido importar apenas arquivos no formato .csv"
            );
        }
    }

    /**
     * @param UploadedFile $file
     * @throws InvalidFileTypeException
     */
    private function checkFileHeader(UploadedFile $file)
    {
        $delimiter = CsvHelper::getFileDelimiter($file);
        $handle = fopen($file, 'r');
        $fileHeader = fgetcsv($handle, null, $delimiter);

        if (!CsvHelper::isValidHeader(self::REQUIRED_FILE_HEADERS, $fileHeader)) {
            throw new InvalidFileTypeException(
                "O cabecalho do arquivo está incorreto. 
                Verifique se está exatamente igual ao modelo disponibilizado para download."
            );
        }
        fclose($handle);
    }

    /**
     * @param UploadedFile $file
     * @param Client $client
     * @return BulkResponse
     */
    private function processFile(UploadedFile $file, Client $client)
    {
        $delimiter = CsvHelper::getFileDelimiter($file);
        $handle = fopen($file, 'r');
        $bulkResponse = new BulkResponse();

        $isLineHeader = false;
        $fileLine = 2;
        while (($data = fgetcsv($handle, null, $delimiter)) !== false) {
            if (!$isLineHeader) {
                $isLineHeader = true;
                continue;
            }
            $this->processInsert($data, $bulkResponse, $fileLine, $client);
            $fileLine++;
        }

        return $bulkResponse;
    }

    /**
     * @param array $data
     * @param BulkResponse $bulkResponse
     * @param int $fileLine
     * @param Client $client
     * @return void
     */
    private function processInsert(array $data, BulkResponse $bulkResponse, $fileLine, Client $client)
    {
        $friendlyNameValidate   = $this->validateFriendlyName($data, $fileLine);
        $vendorValidate         = $this->validateVendor($data, $fileLine);
        $identifierValidate     = $this->validateIdentifier($data, $fileLine);
        $local                  = $data[self::FIELD_LOCAL] ? $data[self::FIELD_LOCAL] : null;
        $group                  = $this->getGroup($data, $client);
        $template               = $this->getTemplate($data, $client);
        $timezoneValidate       = $this->validateTimezone($data, $fileLine);

        if (!$friendlyNameValidate->isValid()) {
            $bulkResponse->addMessage($friendlyNameValidate->getMessage(), BulkResponse::ERROR);
            return;
        }

        if (!$vendorValidate->isValid()) {
            $bulkResponse->addMessage($vendorValidate->getMessage(), BulkResponse::ERROR);
            return;
        }

        if (!$identifierValidate->isValid()) {
            $bulkResponse->addMessage($identifierValidate->getMessage(), BulkResponse::ERROR);
            return;
        }

        if (!$group && !empty($data[self::FIELD_AP_GROUP])) {
            $bulkResponse->addMessage(
                "Linha {$fileLine} - Grupo \"{$data[self::FIELD_AP_GROUP]}\" não existe, ponto de acesso adicionado ao grupo padrão.",
                BulkResponse::WARNING);
        }


        if (!$template && !empty($data[self::FIELD_TEMPLATE])) {
            $bulkResponse->addMessage(
                "Linha {$fileLine} - Template \"{$data[self::FIELD_TEMPLATE]}\" não existe, ponto adicionado com o template padrão.",
                BulkResponse::WARNING
            );
        }

        if (!$timezoneValidate->isValid()) {
            $bulkResponse->addMessage($timezoneValidate->getMessage(), BulkResponse::ERROR);
            return;
        }

        $accessPoint = new AccessPoints();
        $accessPoint->setFriendlyName($friendlyNameValidate->getValue());
        $accessPoint->setVendor($vendorValidate->getValue());
        $accessPoint->setIdentifier($identifierValidate->getValue());
        $accessPoint->setLocal($local);
        $accessPoint->setGroup($group);
        $accessPoint->setTemplate($template);
        $accessPoint->setTimezone($timezoneValidate->getValue());
        $accessPoint->setClient($client);

        try {
            $this->accessPointsService->createOne($accessPoint);
            $bulkResponse->addMessage(
                "Ponto de acesso \"{$friendlyNameValidate->getValue()}\" inserido com sucesso",
                BulkResponse::SUCCESS
            );
        } catch (AccessPointExistsException $e) {
            $bulkResponse->addMessage("Linha {$fileLine} - " . $e->getMessage(), BulkResponse::ERROR);
        } catch (ErrorOnCreateAccessPointException $e) {
            $bulkResponse->addMessage("Linha {$fileLine} - " . $e->getMessage(), BulkResponse::ERROR);
        } catch (\Exception $e) {
            $bulkResponse->addMessage("Linha {$fileLine} - " . $e->getMessage(), BulkResponse::ERROR);
        }
    }

    /**
     * @param array $data
     * @param int $fileLine
     * @return ValidateField
     */
    private function validateFriendlyName(array $data, $fileLine)
    {
        $friendlyName = $data[self::FIELD_FRIENDLY_NAME];

        if (empty($friendlyName)) {
            return new ValidateField(
                false,
                "Linha {$fileLine} - Nome do ponto de acesso esta vazio.",
                $friendlyName
            );
        }

        return new ValidateField(
            true,
            null,
            $friendlyName
        );
    }

    /**
     * @param array $data
     * @param int $fileLine
     * @return ValidateField
     */
    private function validateVendor(array $data, $fileLine)
    {
        $vendor = strtolower($data[self::FIELD_VENDOR]);
        $friendlyName = $data[self::FIELD_FRIENDLY_NAME];

        $allVendors = $this->vendorService->getAllVendorsName();

        if (empty($vendor)) {
            return new ValidateField(
                false,
                "Linha {$fileLine} - Fabricante vazio não permitido no ponto \"{$friendlyName}\",
                 analise a documentação.",
                $vendor
            );
        }

        if (!in_array($vendor,$allVendors)) {
            return new ValidateField(
                false,
                "Linha {$fileLine} - Fabricante {$vendor} não permitido no ponto \"{$friendlyName}\",
                 analise na documentação.",
                $vendor
            );
        }

        $vendor = str_replace(" ", "-", $vendor);
        return new ValidateField(
            true,
            null,
            $vendor
        );
    }

    /**
     * @param array $data
     * @param int $fileLine
     * @return ValidateField
     */
    private function validateIdentifier(array $data, $fileLine)
    {
        $identifier = strtoupper($data[self::FIELD_IDENTIFIER]);
        $vendor = strtolower($data[self::FIELD_VENDOR]);
        $friendlyName = strtolower($data[self::FIELD_FRIENDLY_NAME]);
        $vendorsWithMacAddressMask = $this->vendorService->getVendorsNameWithMacAddressMask();
        $vendorsNameWithoutMacAddressMask = $this->vendorService->getVendorsNameWithoutMacAddressMask();

        if (preg_match('/^([a-fA-F0-9]{2}\:){5}[a-fA-F0-9]{2}$/', $identifier) === 1) {
            $identifier = strtoupper(str_replace(':', '-', $identifier));
        }

        if (in_array($vendor, $vendorsWithMacAddressMask)) {
            if (!preg_match('/^([A-Z0-9]{2}-){5}([A-Z0-9]{2})$/', $identifier)) {
                return new ValidateField(
                    false,
                    "Linha {$fileLine} - Idenfiticador \"{$identifier}\" inválido no ponto \"{$friendlyName}\"",
                    $identifier
                );
            }
            return new ValidateField(
                true,
                null,
                $identifier
            );
        }

        if (in_array($vendor, $vendorsNameWithoutMacAddressMask)) {
            return new ValidateField(
                true,
                null,
                $identifier
            );
        }

        return new ValidateField(
            false,
            "Linha {$fileLine} - Identificador \"{$identifier}\" do ponto de acesso \"{$friendlyName}\" 
            inválido, veja a documentação.",
            $identifier
        );
    }

    /**
     * @param array $data
     * @param $fileLine
     * @return ValidateField
     */
    private function validateTimezone(array $data, $fileLine)
    {
        $timezoneFromImportedDoc = $data[self::FIELD_TIMEZONE];
        $timezoneFromDatabase = $this->timezoneService->getTimezoneByZoneName($timezoneFromImportedDoc);

        if (empty($timezoneFromImportedDoc)) {
            return new ValidateField(
                false,
                "Linha {$fileLine} - Timezone vazio, este campo é obrigatório, favor preencher.",
                $timezoneFromImportedDoc
            );
        }

        if (is_null($timezoneFromDatabase)) {
            return new ValidateField(
                false,
                "Linha {$fileLine} - Timezone '{$timezoneFromImportedDoc}' não existe em nossa base de dados.",
                $timezoneFromImportedDoc
            );
        }

        return new ValidateField(
            true,
            null,
            $timezoneFromImportedDoc
        );
    }

    /**
     * @param array $data
     * @param Client $client
     * @return AccessPointsGroups | null
     */
    private function getGroup(array $data, Client $client)
    {
        $groupName = $data[self::FIELD_AP_GROUP];
        $group = $this->accessPointsGroupsService->getGroupByName($groupName, $client);
        if (empty($group) || is_null($group)) {
            $group = $this->accessPointsGroupsService->getGroupByName(self::DEFAUlT_GROUP, $client);
        }
        return $group;
    }

    /**
     * @param array $data
     * @param Client $client
     * @return Template | null
     */
    private function getTemplate(array $data, Client $client)
    {
        $templateName = $data[self::FIELD_TEMPLATE];

        if (empty($templateName)) {
            return null;
        }

        return $this->templateService->getTemplateByNameOrDefault($templateName, $client);
    }

}
