<?php

namespace Wideti\DomainBundle\Service\AccessCode;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\AccessCodeDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeSettings;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\FrontendBundle\Factory\Nas;

interface AccessCodeService
{
    public function createDefaultSettings(Client $client);
    public function generateLotNumber();
    public function generateRandomAccessCodes(AccessCode $accessCode);
    public function create(AccessCode $accessCode, $extraParams);
    public function update(AccessCode $accessCode);
    public function updatePreDefinedCode(AccessCode $accessCode, $preDefinedCode, $newPreDefinedCode);
    public function delete(AccessCode $accessCode);
    public function preferences(AccessCodeSettings $entity);
    public function findAccessCode(AccessCodeDto $accessCodeDto, $inputCode);
    public function validateCode(AccessCodeDto $accessCodeDto, Nas $nas);
    public function setAccessCodeAsUsed(Guest $guest, $params);
    public function countUsed(AccessCode $accessCode);
    public function getAllCodesByLot(AccessCode $accessCode);
    public function getAvailableAccessCodes(Nas $nas = null, $step);
    public function uploadImage(UploadedFile $file, AccessCode $entity);
    public function deleteImage(AccessCode $entity);
    public function validateImage(UploadedFile $file);
    public function setFileUpload(FileUpload $fileUpload);
    public function setValidator(ValidatorInterface $validator);
}
