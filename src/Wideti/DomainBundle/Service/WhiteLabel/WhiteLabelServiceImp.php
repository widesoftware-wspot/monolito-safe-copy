<?php

namespace Wideti\DomainBundle\Service\WhiteLabel;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\FrontendBundle\Factory\Nas;

class WhiteLabelServiceImp implements WhiteLabelService
{
    private $bucket;
	/**
	 * @var EntityManager
	 */
	private $em;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var Session
     */
    private $session;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;
	/**
	 * @var FileUpload
	 */
	protected $fileUpload;
	/**
	 * @var ValidatorInterface
	 */
	protected $validator;

    /**
     * @param $bucket
     * @param EntityManager $em
     * @param ConfigurationService $configurationService
     * @param Session $session
     * @param CacheServiceImp $cacheService
     * @param FileUpload $fileUpload
     * @param ValidatorInterface $validator
     */
    public function __construct(
        $bucket,
    	EntityManager $em,
	    ConfigurationService $configurationService,
	    Session $session,
		CacheServiceImp $cacheService,
		FileUpload $fileUpload,
		ValidatorInterface $validator
    ) {
        $this->bucket = $bucket;
        $this->configurationService = $configurationService;
        $this->session = $session;
	    $this->cacheService = $cacheService;
	    $this->fileUpload = $fileUpload;
	    $this->validator = $validator;
	    $this->em = $em;
    }

	/**
	 * @param WhiteLabel $whiteLabel
	 * @return WhiteLabel
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function update(WhiteLabel $whiteLabel)
    {
        $this->em->persist($whiteLabel);
        $this->em->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->remove(CacheServiceImp::WHITE_LABEL);
        }

        return $whiteLabel;
    }

    public function uploadImage(UploadedFile $file, WhiteLabel $entity)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $this->validateImage($file);

        $newFileName = $this->fileUpload->generateFileName($file);
        $this->fileUpload->uploadFile(
            $file,
            $newFileName,
            $this->bucket,
            $this->configurationService->get($nas, $entity->getClient(), 'aws_folder_name')
        );

        $entity->setLogotipo($newFileName);

        $this->em->persist($entity);
        $this->em->flush();
    }

    public function deleteImage(WhiteLabel $entity)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);

        $this->fileUpload
            ->deleteFile(
                $entity->getLogotipo(),
                $this->bucket,
                $this->configurationService->get($nas, $entity->getClient(), 'aws_folder_name')
            );

        $entity->setLogotipo(null);

        $this->em->persist($entity);
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
                            'maxSize' => '2M'
                        ]
                    ),
                    new NotBlank()
                ]
            ]
        );

        $errors = $this->validator->validate($validateFile, $validation);

        if (count($errors) !== 0) {
            throw new HttpException(400, $errors[0]->getMessage());
        }
    }

    /**
     * @param FileUpload $fileUpload
     */
    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function getDefaultWhiteLabel()
    {
        return [
            'companyName'   => 'Mambo WiFi',
            'panelColor'    => '#ec213a',
            'logotipo'      => '/bundles/admin/frontend/images/logo-mambo.png',
            'signature'     => '© {ano} - Mambo WIFi - <a href="https://www.mambowifi.com" target="_blank">www.mambowifi.com</a>'
        ];
    }

    public function setDefault(Client $client, $whitelabelIdentity=[])
    {

	    $whiteLabel = new WhiteLabel();
	    $whiteLabel->setClient($client);
        if (array_key_exists('company_name', $whitelabelIdentity) &&
            array_key_exists('panel_color', $whitelabelIdentity) &&
            array_key_exists('logotipo', $whitelabelIdentity) &&
            array_key_exists('signature', $whitelabelIdentity)) {
            $whiteLabel->setCompanyName($whitelabelIdentity['company_name']);
            $whiteLabel->setPanelColor($whitelabelIdentity['panel_color']);
            $whiteLabel->setLogotipo($whitelabelIdentity['logotipo']);
            $whiteLabel->setSignature($whitelabelIdentity['signature']);
        } else {
            $default = $this->getDefaultWhiteLabel();
            $whiteLabel->setCompanyName($default['companyName']);
            $whiteLabel->setPanelColor($default['panelColor']);
            $whiteLabel->setLogotipo($default['logotipo']);
            $whiteLabel->setSignature($default['signature']);

        }
	    $this->em->persist($whiteLabel);
	    $this->em->flush();
    }

    /**
     * @param $whiteLabelIdentity
     * @param $clientIds
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateWhiteLabelsByClientIds($whiteLabelIdentity=[], $clientIds) {
        $companyName = $whiteLabelIdentity['company_name'];
        $logotipo = $whiteLabelIdentity['logotipo'];
        $panelColor = $whiteLabelIdentity['panel_color'];
        $signature = $whiteLabelIdentity['signature'];

        $query = "
            UPDATE white_label SET
                company_name = '{$companyName}',
                logotipo = '{$logotipo}',
                panel_color = '{$panelColor}',
                signature = '{$signature}'
            WHERE client_id IN {$clientIds}
        ";

        $connection = $this->em->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
    }
}
