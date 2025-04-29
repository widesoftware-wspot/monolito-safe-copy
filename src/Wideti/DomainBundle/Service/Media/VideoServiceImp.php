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
use Wideti\DomainBundle\Entity\CampaignMediaVideo;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class VideoServiceImp implements MediaService
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
    private $videoSourceBucket;
    private $videoDestinationBucket;

    /**
     * ImageServiceImp constructor.
     * @param EntityManager $entityManager
     * @param ConfigurationService $configurationService
     * @param ValidatorInterface $validator
     * @param FileUpload $fileUpload
     * @param $videoSourceBucket
     * @param $videoDestinationBucket
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigurationService $configurationService,
        ValidatorInterface $validator,
        FileUpload $fileUpload,
        $videoSourceBucket,
        $videoDestinationBucket
    ) {
        $this->entityManager = $entityManager;
        $this->configurationService = $configurationService;
        $this->validator = $validator;
        $this->fileUpload = $fileUpload;
        $this->videoSourceBucket = $videoSourceBucket;
        $this->videoDestinationBucket = $videoDestinationBucket;
    }

    /**
     * @param UploadedFile $file
     * @param $folder
     * @param array $options
     * @return mixed
     */
    public function upload(UploadedFile $file, $bucket, $folder, array $options)
    {
        $validate = $this->validate($file);

        if (count($validate) !== 0) {
            throw new HttpException(400, $validate[0]->getMessage());
        }

        $fileName = isset($options['fileName'])
            ? "{$options['fileName']}.{$file->guessExtension()}"
            : $this->fileUpload->generateFileName($file);

        return $this->saveFile($bucket, $file, $fileName);
    }

    /**
     * @param UploadedFile $file
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    private function validate(UploadedFile $file)
    {
        $validateFile['file'] = $file;

        $validation = new Collection(
            [
                'file' => [
                    new File(
                        [
                            'mimeTypes' => 'video/mp4',
                            'maxSize'   => '50M'
                        ]
                    ),
                    new NotBlank()
                ],
            ]
        );

        return $this->validator->validate($validateFile, $validation);
    }

    /**
     * @param $bucket
     * @param UploadedFile $file
     * @param $fileName
     * @return mixed
     */
    private function saveFile($bucket, UploadedFile $file, $fileName)
    {
        $this->fileUpload->uploadFile(
            $file,
            $fileName,
            $bucket,
            'videos'
        );

        return $fileName;
    }

    /**
     * @param Campaign $campaign
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Campaign $campaign)
    {
        $files  = $campaign->getCampaignMediaVideo();

        /**
         * @var CampaignMediaVideo $file
         */
        foreach ($files as $file) {
            $fileName = str_replace("m3u8", "mp4", explode("hls/", $file->getUrl())[1]);
            $folder = $file->getBucketId();

            $this->removeFileFromSource($this->videoSourceBucket, $fileName);
            $this->removeFileFromDestination($this->videoDestinationBucket, $folder);

            $this->entityManager->remove($file);
            $this->entityManager->flush();
        }
    }

    private function removeFileFromSource($bucket, $fileName)
    {
        $this->fileUpload->deleteFile(
            $fileName,
            $bucket,
            'videos'
        );
    }

    private function removeFileFromDestination($bucket, $folder)
    {
        $this->fileUpload->deleteAllFiles(
            $bucket,
            $folder
        );
    }
}
