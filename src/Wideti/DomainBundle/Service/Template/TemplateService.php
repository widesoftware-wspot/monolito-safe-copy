<?php

namespace Wideti\DomainBundle\Service\Template;

use Aws\Sns\Exception\NotFoundException;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Template\TemplateSelector\TemplateSelector;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\DomainBundle\Entity\Template;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class TemplateService
{
    use EntityManagerAware;
    use SecurityAware;
    use SessionAware;

    private $bucket;
    /**
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var ValidatorInterface
     */
    protected $validator;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var TemplateSelector
     */
    private $templateSelector;
    /**
     * @var Logger
     */
    private $logger;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;

    /**
     * TemplateService constructor.
     * @param $bucket
     * @param ConfigurationService $configurationService
     * @param TemplateSelector $templateSelector
     * @param CacheServiceImp $cacheService
     * @param Logger $logger
     */
    public function __construct(
        $bucket,
        ConfigurationService $configurationService,
        TemplateSelector $templateSelector,
        CacheServiceImp $cacheService,
        Logger $logger
    ) {
        $this->configurationService = $configurationService;
        $this->templateSelector = $templateSelector;
	    $this->cacheService = $cacheService;
	    $this->logger = $logger;
        $this->bucket = $bucket;
    }

    public function create(Template $template)
    {
        if (!$template->getClient()) {
            $client = $this->em
                ->getRepository("DomainBundle:Client")
                ->find($this->getLoggedClient())
            ;

            if ($client == null) {
                throw new NotFoundException('Client not found');
            }

            $template->setClient($client);
        }

        $this->em->persist($template);
        $this->em->flush();
    }

    public function update(Template $template)
    {
        $this->em->persist($template);
        $this->em->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
        }
    }

    public function delete(Template $template)
    {
	    $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
	    $client = $this->session->get('wspotClient');
	    $folder = $this->configurationService->get($nas, $client, 'aws_folder_name');

	    try {
		    $this->em->remove($template);
		    $this->em->flush();

		    $this->fileUpload->deleteFile($template->getPartnerLogo(), $this->bucket, $folder);

		    $this->deleteImage(new LandscapeImage($template));
		    $this->deleteImage(new PortraitImage($template));

		    if ($this->cacheService->isActive()) {
			    $this->cacheService->removeAllByModule(CacheServiceImp::TEMPLATE_MODULE);
		    }
	    } catch (\Exception $ex) {
		    throw $ex;
	    }
    }

    public function uploadImage(TemplateImage $templateImage)
    {
        $this->fileUpload->uploadFile(
            $templateImage->getFile(),
            $templateImage->getFileName(),
            $this->bucket,
            (!$templateImage->hasCustomBucket()) ? $this->configurationService->get(
                $this->session->get(Nas::NAS_SESSION_KEY),
                $this->session->get('wspotClient'),
                'aws_folder_name'
            ) : $templateImage->hasCustomBucket()
        );

        $templateImage->setImage($templateImage->getFileName());

        $this->em->flush();
    }

    public function deleteImage(TemplateImage $templateImage)
    {
	    if ($templateImage->hasCustomImage()) {
		    $images = $templateImage->getImagesToRemove();
	    } else {
		    $images = [ $templateImage->getImage() ];
	    }

	    foreach ($images as $image) {
            $this->fileUpload->deleteFile(
                $image,
                $this->bucket,
                (is_null($templateImage->hasCustomBucket())) ? $this->configurationService->get(
                    $this->session->get(Nas::NAS_SESSION_KEY),
                    $this->session->get('wspotClient'),
                    'aws_folder_name'
                ) : $templateImage->hasCustomBucket()
            );
        }

        $templateImage->setNullImage();

        $this->em->flush();
    }

    /**
     * @param  UploadedFile $file
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function validateImage(UploadedFile $file)
    {
        $validateFile['partnerLogo'] = $file;

        $validation = new Collection(
            [
                'partnerLogo' => [
                    new Image(),
                    new File(
                        [
                        'maxSize' => '2M',
                        ]
                    ),
                    new NotBlank(),
                ],
            ]
        );

        $errors = $this->validator->validate($validateFile, $validation);

        if (count($errors) !== 0) {
            throw new HttpException(400, $errors[0]->getMessage());
        }
    }

    /**
     * Setters
     */
    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param null $campaignId
     * @return Template
     */
    public function templateSettings($campaignId = null)
    {
        /** @var Nas $nas */
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');
        $campaign = null;

        if ($campaignId) {
            $campaign = $this->em
                ->getRepository("DomainBundle:Campaign")
                ->find($campaignId);
        }

        return $this->templateSelector->select($nas, $client, $campaign);
    }

    /**
     * @param $templateName
     * @param Client $client
     * @return Template | null
     */
    public function getTemplateByNameOrDefault($templateName, Client $client)
    {
        $repository = $this->em->getRepository('DomainBundle:Template');
        $template = $repository
            ->findOneBy([
                'client' => $client,
                'name' => trim($templateName)
            ]);

        if (!$template) {
            $template = $repository->defaultTemplate($client);
        }

        return $template;
    }

    /**
     * @param string $apIdentifier
     * @param int $campaignId
     * @param Client $client
     * @return Template
     *
     * @description This method will return the template fallowing the priority order:
     * 1)Campaign
     * 2)Ap
     * 3)Default
     */
    public function getTemplateBy(Client $client, $apIdentifier = null, $campaignId = null)
    {
        $campaignRepo    = $this->em->getRepository('DomainBundle:Campaign');
        $accessPointRepo = $this->em->getRepository('DomainBundle:AccessPoints');
        $templateRepo    = $this->em->getRepository('DomainBundle:Template');

        $template = null;

        if ($campaignId) {
            $campaign = $campaignRepo->findOneBy([
                'id' => $campaignId,
                'client' => $client
            ]);

            if ($campaign) {
                $template = $campaign->getTemplate();
            }
        }

        if ($apIdentifier && !$template) {
            $accessPoint = $accessPointRepo->findOneBy([
                'identifier' => $apIdentifier,
                'client' => $client
            ]);

            if ($accessPoint) {
                $template = $accessPoint->getTemplate();
            }
        }

        if (!$template) {
            $template = $templateRepo->findOneBy([
                'client' => $client
            ]);
        }

        return $template;
    }

    /**
     * @param int $id
     * @description This method will return the template 
     */
    public function getTemplateById($id)
    {
        return $this->em->getRepository('DomainBundle:Template')->findOneBy(['id' => $id]);
    }
}
