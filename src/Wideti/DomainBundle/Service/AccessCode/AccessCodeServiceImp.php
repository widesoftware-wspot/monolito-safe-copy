<?php

namespace Wideti\DomainBundle\Service\AccessCode;

use mysql_xdevapi\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\AccessCodeDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeCodes;
use Wideti\DomainBundle\Entity\AccessCodeSettings;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Repository\AccessCodeCodesRepository;
use Wideti\DomainBundle\Repository\AccessCodeRepository;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Radacct\RadacctServiceAware;
use Wideti\DomainBundle\Service\Radcheck\RadcheckAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Rhumsaa\Uuid\Uuid;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

/**
 * Class AccessCodeService
 * @package Wideti\DomainBundle\Service\AccessCode
 */
class AccessCodeServiceImp implements AccessCodeService
{
    use EntityManagerAware;
    use MongoAware;
    use SecurityAware;
    use RadcheckAware;
    use TranslatorAware;
    use ModuleAware;
    use SessionAware;
    use RadacctServiceAware;
    use GuestServiceAware;

    private $bucket;
    /**
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    protected $loteNumberSize = 3;
    protected $hashSize       = 3;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var AccessCodeRepositor
     */
    private $accessCodeRepository;
    /**
     * @var AccessCodeCodesRepository
     */
    private $accessCodeCodesRepository;

    /**
     * @param $bucket
     * @param ConfigurationService $configurationService
     * @param CacheServiceImp $cacheService
     * @param AccessCodeRepository $accessCodeRepository
     * @param AccessCodeCodesRepository $accessCodeCodesRepository
     */
    public function __construct(
        $bucket,
        ConfigurationService $configurationService,
        CacheServiceImp $cacheService,
        AccessCodeRepository $accessCodeRepository,
        AccessCodeCodesRepository $accessCodeCodesRepository
    ) {
        $this->configurationService      = $configurationService;
        $this->cacheService              = $cacheService;
        $this->accessCodeRepository      = $accessCodeRepository;
        $this->accessCodeCodesRepository = $accessCodeCodesRepository;
        $this->bucket = $bucket;
    }

    public function createDefaultSettings(Client $client)
    {
        $parser = new Parser();

        $settings = $parser->parse(
            file_get_contents(__DIR__ . '/../../DataFixtures/ORM/Fixtures/Default/access_code_settings.yml')
        );

        foreach ($settings['Wideti\DomainBundle\Entity\AccessCodeSettings'] as $item) {
            $settings = new AccessCodeSettings();
            $settings->setClient($client);
            $settings->setEnableFreeAccess($item['enableFreeAccess']);
            $settings->setFreeAccessTime($item['freeAccessTime']);
            $settings->setFreeAccessPeriod($item['freeAccessPeriod']);
            $settings->setInAccessPoints($item['inAccessPoints']);

            $this->em->persist($settings);
            $this->em->flush();
        }
    }

    public function generateLotNumber()
    {
        $clientId = $this->getLoggedClient();
        $exists   = null;

        do {
            $uuid      = Uuid::uuid4();
            $lotNumber = strtoupper(substr(md5($uuid->toString() . $clientId->getId()), 0, $this->loteNumberSize));

            $exists = $this->em
                ->getRepository('DomainBundle:AccessCode')
                ->findOneBy([
                    'lotNumber' => $lotNumber
                ]);
        } while ($exists);

        return $lotNumber;
    }

    /**
     * @param AccessCode $accessCode
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateRandomAccessCodes(AccessCode $accessCode)
    {
        $codesGenerated = [];
        $repeated       = [];
        $quantity       = $accessCode->getQuantity();
        $lotNumber      = $accessCode->getLotNumber();

        for ($i = 0; $i < $quantity; $i++) {
            $uuid       = Uuid::uuid4();
            $hash       = strtoupper(substr(md5($uuid->toString()), 0, $this->hashSize));
            $codeHash   = $hash . $lotNumber;
            if (in_array($codeHash, $codesGenerated)) {
                array_push($repeated, $codesGenerated);
                $i--;
                continue;
            }

            $codes = new AccessCodeCodes();
            $codes->setAccessCode($accessCode);
            $codes->setCode($codeHash);
            $codesGenerated[] = $codeHash;

            $this->em->persist($codes);
        }
        $this->em->flush();
    }

    /**
     * @param AccessCode $accessCode
     * @param $extraParams
     * @return AccessCode
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(AccessCode $accessCode, $extraParams)
    {
        $client = $this->em->getRepository('DomainBundle:Client')
            ->findOneBy([
                'domain' => $this->getLoggedClient()->getDomain()
            ]);
        $type   = $accessCode->getType();

        $accessCode->setClient($client);

        if ($accessCode->getPeriodTo()) {
            $periodTo = new \DateTime(date_format($accessCode->getPeriodTo(), 'Y-m-d 23:59:59'));
            $accessCode->setPeriodTo($periodTo);
        }

        $this->checkIfAlreadyExists($accessCode);

        $this->em->persist($accessCode);
        $this->em->flush();

        if ($type == AccessCode::TYPE_PREDEFINED) {
            $preDefinedCode = strtoupper($extraParams['code']);
            $codes = new AccessCodeCodes();
            $codes->setAccessCode($accessCode);
            $codes->setCode($preDefinedCode);
            $this->em->persist($codes);
            $this->em->flush();
        }

        if ($type == AccessCode::TYPE_RANDOM) {
            $accessCode->setLotNumber($this->generateLotNumber());
            $accessCode->setQuantity((int)$accessCode->getQuantity());
            $this->generateRandomAccessCodes($accessCode);
        }

        return $accessCode;
    }

    public function update(AccessCode $accessCode)
    {
        $this->checkIfAlreadyExists($accessCode);

        if ($accessCode->getPeriodTo()) {
            $periodTo = new \DateTime(date_format($accessCode->getPeriodTo(), 'Y-m-d 23:59:59'));
            $accessCode->setPeriodTo($periodTo);
        }

        $this->em->persist($accessCode);
        $this->em->flush();

        return $accessCode;
    }

    /**
     * @param AccessCode $accessCode
     * @param $preDefinedCode
     * @param $newPreDefinedCode
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updatePreDefinedCode(AccessCode $accessCode, $preDefinedCode, $newPreDefinedCode)
    {
        return $this->accessCodeRepository->updatePreDefinedCode($accessCode, $preDefinedCode, $newPreDefinedCode);
    }

    /**
     * @param AccessCode $accessCode
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(AccessCode $accessCode)
    {
        $this->em->remove($accessCode);
        $this->em->flush();
    }

    public function preferences(AccessCodeSettings $entity)
    {
        $accessPoints = $entity->getAccessPoints();
        $entity->setAccessPoints([]);

        foreach ($accessPoints as $aps) {
            $entity->addAccessPoint($aps);
        }

        $this->em->persist($entity);
        $this->em->flush();
    }

    public function findAccessCode(AccessCodeDto $accessCodeDto, $inputCode)
    {
        return $this->accessCodeRepository->findAccessCodeByCode($accessCodeDto, $inputCode);
    }

    public function validateCode(AccessCodeDto $accessCodeDto, Nas $nas)
    {
    	$client  = $this->getLoggedClient();
        $guestId = isset($accessCodeDto->getAccessCodeParams()['username'])
		    ? $accessCodeDto->getAccessCodeParams()['username']->getId()
		    : null;



	    $guestDeviceMacAddress = $nas->getGuestDeviceMacAddress();
	    $exists = $this->guestService->hasGuestByMacAddressAndGuestId($guestDeviceMacAddress, $guestId);

	    if ($exists) {
            $hasExpiration = $this->radcheckService->checkIfGuestHasExpiration($client, $guestId);
            if ($hasExpiration) return null;
	    }

        /**
         * @var AccessCodeCodes $accessCodeLot
         */
	    $accessCodeLot = $this->accessCodeCodesRepository->getCodeById($accessCodeDto->getAccessCodeParams()['code']);
        
	    $lot = null;
	    $isLotValid = null;

        if (!$accessCodeLot) {
            return $this->translator->trans('wspot.access_code.invalid_code');
        }

	    if ($accessCodeLot) {
            $lot = $accessCodeLot->getAccessCode();
        }

	    if ($lot) {
            $isLotValid = $this->lotValidate($lot);
        }

	    if (!$isLotValid) {
	        return $this->translator->trans('wspot.access_code.expired_lot');
        }

        if ($accessCodeLot->getUsed() == true) {
            return $this->translator->trans('wspot.access_code.used_code');
        }

        return null;
    }

    public function lotValidate(AccessCode $accessCode)
    {
        if (!$accessCode) {
            return false;
        }

        if (!$accessCode->getPeriodFrom()) {
            return true;
        }

        $periodFrom = date_format($accessCode->getPeriodFrom(), 'Y-m-d');
        $periodTo   = $accessCode->getPeriodTo()
            ? date_format($accessCode->getPeriodTo(), 'Y-m-d')
            : $periodTo = date('Y-m-d');

        $dateNow = date('Y-m-d');

        if ($dateNow < $periodFrom || $dateNow > $periodTo) {
            return false;
        }

        return true;
    }

    public function setAccessCodeAsUsed(Guest $guest, $params)
    {
        $this->accessCodeCodesRepository->setAccessCodeAsUsed($guest, $params['code']);
    }

    /**
     * @param AccessCode $accessCode
     * @return int
     */
    public function countUsed(AccessCode $accessCode)
    {
        return $this->accessCodeRepository->countUsed($accessCode);
    }

    /**
     * @param AccessCode $accessCode
     * @return array|null|object
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getAllCodesByLot(AccessCode $accessCode)
    {
        return $this->accessCodeRepository->getAllByLot($accessCode);
    }

    private function checkIfAlreadyExists(AccessCode $accessCode)
    {
        $inAccessPoints = $accessCode->getInAccessPoints();

        if (!$inAccessPoints) {
            $exists = $this->em->getRepository('DomainBundle:AccessCode')
                ->checkIfAlreadyExists($accessCode);

            if ($exists) {
                throw new \Exception(
                    'accessCodeAlreadyExists'
                );
            }
        }

        if ($inAccessPoints) {
            $apsId = [];

            foreach ($accessCode->getAccessPoints() as $aps) {
                array_push($apsId, $aps->getId());
            }

            $exists = $this->em->getRepository('DomainBundle:AccessCode')
                ->checkIfAlreadyExists($accessCode, $apsId);

            if ($exists) {
                throw new \Exception(
                    'accessCodeAlreadyExists'
                );
            }
        }

        return false;
    }

    /**
     * @param Nas|null $nas
     * @param $step
     * @return AccessCodeDto
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAvailableAccessCodes(Nas $nas = null, $step)
    {
        $client         = $this->getLoggedClient();
        $identifier     = $nas->getAccessPointMacAddress();
        $accessCodeDto  = new AccessCodeDto();

        if ($this->moduleService->checkModuleIsActive('access_code')) {
            $accessPoint    = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointByIdentifier($identifier, $client);
            $accessPointId = ($accessPoint) ? $accessPoint[0]->getId() : '';

            $accessCodesEntity = $this->em->getRepository('DomainBundle:AccessCode')
                ->getAccessCodeByStepAndAccessPoint($client, $step, $accessPointId);

            if ($accessCodesEntity) {
                $entityIds      = [];
                $accessCodeIds  = [];

                foreach ($accessCodesEntity as $accessCode) {
                    $accessCodesArray = $this->em->getRepository('DomainBundle:AccessCodeCodes')
                        ->findBy([
                            'accessCode' => $accessCode['id']
                        ]);

                    foreach ($accessCodesArray as $code) {
                        array_push($accessCodeIds, $code->getCode());
                    }
                    array_push($entityIds, (int)$accessCode['id']);
                }

                $accessCodeDto->setHasAccessCode(true);
                $accessCodeDto->setAccessCodeIds($entityIds);
                $accessCodeDto->setAccessCodeParams([
                    'accessCodeIds'  => $accessCodeIds
                ]);
            }

            $freeAccess = $this->em->getRepository('DomainBundle:AccessCodeSettings')
                ->getSettingsByFilter([
                    'client'        => $client,
                    'enable'        => true,
                    'accessPoint'   => $accessPointId
                ]);

            if ($freeAccess) {
                $accessCodeDto->setHasFreeAccess(true);
                $accessCodeDto->setFreeAccessParams([
                    'freeAccessTime'    => $freeAccess['free_access_time'],
                    'freeAccessPeriod'  => $freeAccess['free_access_period'],
                    'endPeriodText'     => $freeAccess['end_period_text']
                ]);
            }

            if (!$this->session->get('accessCodeDto')) {
                $this->session->set('accessCodeDto', $accessCodeDto);
            }

            return $accessCodeDto;
        }

        return $accessCodeDto;
    }

    public function uploadImage(UploadedFile $file, AccessCode $entity)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');
        $this->validateImage($file);

        $newFileName = $this->fileUpload->generateFileName($file);

        $this->fileUpload->uploadFile(
            $file,
            $newFileName,
            $this->bucket,
            $this->configurationService->get($nas, $client, 'aws_folder_name')
        );

        $entity->setLogotipo($newFileName);

        $this->em->persist($entity);
        $this->em->flush();
    }

    public function deleteImage(AccessCode $entity)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');

        $this->fileUpload
            ->deleteFile(
                $entity->getLogotipo(),
                $this->bucket,
                $this->configurationService->get($nas, $client, 'aws_folder_name')
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
                    new Image(
                        [
                            'maxWidth' => '250',
                            'maxHeight' => '250'
                        ]
                    ),
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

    /**
     * @param $accessCode
     */
    public function findByCodeUsed($accessCode) {
        return $this->accessCodeCodesRepository->findByCodeUsed($accessCode);
    }
    public function getRadcheckExpirationByCode($accessCode) {
        return $this->accessCodeCodesRepository->getRadcheckExpirationByCode($accessCode);
    }
}
