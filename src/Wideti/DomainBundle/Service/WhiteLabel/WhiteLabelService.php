<?php

namespace Wideti\DomainBundle\Service\WhiteLabel;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Helpers\FileUpload;

interface WhiteLabelService
{
    public function update(WhiteLabel $whiteLabel);
    public function uploadImage(UploadedFile $file, WhiteLabel $entity);
    public function deleteImage(WhiteLabel $entity);
    public function validateImage(UploadedFile $file);
    public function setFileUpload(FileUpload $fileUpload);
    public function setValidator(ValidatorInterface $validator);
    public function getDefaultWhiteLabel();
    public function setDefault(Client $client, $whitelabelIdentity=[]);
    public function updateWhiteLabelsByClientIds($whiteLabelIdentity=[], $clientIds);

}
