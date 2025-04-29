<?php

namespace Wideti\DomainBundle\Service\Media;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignMediaImage;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ImageServiceImp implements MediaService
{
    use SessionAware;
    use SecurityAware;

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var FileUpload
     */
    private $fileUpload;
    private $bucket;

    /**
     * ImageServiceImp constructor.
     * @param EntityManager $entityManager
     * @param ConfigurationService $configurationService
     * @param ValidatorInterface $validator
     * @param FileUpload $fileUpload
     * @param $bucket
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigurationService $configurationService,
        ValidatorInterface $validator,
        FileUpload $fileUpload,
        $bucket
    ) {
        $this->entityManager = $entityManager;
        $this->configurationService = $configurationService;
        $this->validator = $validator;
        $this->fileUpload = $fileUpload;
        $this->bucket = $bucket;
    }

    /**
     * @param UploadedFile $file
     * @param array $folder
     * @param array $options
     * @return mixed
     */
    public function upload(UploadedFile $file, $bucket, $folder, array $options)
    {
        
        $type       = $options['type'];
        $validate   = $this->validate($file, $type);

        if (count($validate) !== 0) {
            throw new HttpException(400, $validate[0]->getMessage());
        }

        return $this->saveImage($file, $this->fileUpload->generateFileName($file), $bucket, $folder);
    }

    /**
     * @param UploadedFile $file
     * @param $type
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    private function validate(UploadedFile $file, $type)
    {
        $minWidth  = null;
        $minHeight = null;

        if ($type == 'desktop') {
            $minWidth  = 10;
            $minHeight = 10;
        }

        if ($type == 'mobile') {
            $minWidth  = 10;
            $minHeight = 10;
        }

        $validateFile['img'] = $file;

        $validation = new Collection(
            [
                'img' => [
                    new Image(
                        [
                            'minWidth'  => $minWidth,
                            'minHeight' => $minHeight,
                            "mimeTypesMessage" => "Formato de imagem inválido"
                        ]
                    ),
                    new File(
                        [
                            'maxSize' => '4M'
                        ]
                    ),
                    new NotBlank(),
                ],
            ]
        );

        return $this->validator->validate($validateFile, $validation);
    }

    private function saveImage(UploadedFile $file, $fileName, $bucket, $folder)
    {
        $this->fileUpload->uploadFile(
            $file,
            $fileName,
            $bucket,
            $folder
        );

        return $fileName;
    }

    /**
     * @param Campaign $campaign
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Campaign $campaign)
    {
        $folder = $campaign->getClient()->getDomain();
        $files  = $campaign->getCampaignMediaImage();

        /**
         * @var CampaignMediaImage $image
         */
        foreach ($files as $file) {
            $this->removeFile($file->getImageDesktop(), $this->bucket, $folder);
            $this->removeFile($file->getImageMobile(), $this->bucket, $folder);

            $this->entityManager->remove($file);
            $this->entityManager->flush();
        }
    }

    private function removeFile($fileName, $bucket, $folder)
    {
        if ($fileName) {
            $this->fileUpload->deleteFile(
                $fileName,
                $bucket,
                $folder
            );
        }
    }
}
