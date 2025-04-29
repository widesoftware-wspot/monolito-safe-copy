<?php

namespace Wideti\DomainBundle\Service\BusinessHours;

use Aws\Sns\Exception\NotFoundException;
use Wideti\DomainBundle\Entity\BusinessHours;
use Wideti\DomainBundle\Entity\BusinessHoursItem;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ModuleConfigurationValue;
use Wideti\DomainBundle\Helpers\BusinessHoursHelper;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Service\BusinessHours\Dto\DtoCheckAvailable;
use Carbon\Carbon;

class BusinessHoursService
{
    use EntityManagerAware;
    use LoggerAware;
    use PaginatorAware;
    use SecurityAware;
    use ModuleAware;
    use SessionAware;

    /**
     * @var TimezoneService $timezoneService
     */
    private $timezoneService;

    /**
     * @var auditLogService
     */
    private $auditLogService;

    /**
     * BusinessHoursService constructor.
     * @param TimezoneService $timezoneService
     */
    public function __construct(TimezoneService $timezoneService, AuditLogService $auditLogService)
    {
        $this->timezoneService = $timezoneService;
        $this->auditLogService = $auditLogService;
    }

	/**
	 * @param BusinessHours $businessHours
	 * @param $data
	 * @return BusinessHours
	 */
    public function create(BusinessHours $businessHours, $data)
    {
	    if (!$businessHours->getClient()) {
		    $client = $this->em
			    ->getRepository("DomainBundle:Client")
			    ->find($this->getLoggedClient())
		    ;

		    if ($client == null) {
			    throw new NotFoundException('Client not found');
		    }

		    $businessHours->setClient($client);
	    }

		$businessHours->setInAccessPoints($data['inAccessPoints'] ?: 0);

        foreach ($data['accessPoints'] as $accessPoint) {
            $businessHours->addAccessPoint($accessPoint);
        }

        try {
            $this->em->persist($businessHours);
            $this->em->flush();

	        foreach ($this->daysOfWeek() as $day) {
                foreach ($data[$day] as $dayInterval) {
		            $this->persistBusinessHoursItem($businessHours, $day, $dayInterval["from"], $dayInterval["to"]);
	            }
            }
        } catch (\Exception $ex) {
            $this->logger->addCritical("Erro ao inserir horário de funcionamento" . $ex->getMessage());
        }

	    return $businessHours;
    }

    /**
     * @param BusinessHours $businessHours
     * @return BusinessHours
     */
    public function update(BusinessHours $businessHours, $data)
    {
    	$this->removeRelationships($businessHours);

	    $businessHours->setInAccessPoints($data['inAccessPoints'] ?: 0);

	    foreach ($data['accessPoints'] as $accessPoint) {
		    $businessHours->addAccessPoint($accessPoint);
	    }

	    try {
		    $this->em->persist($businessHours);
		    $this->em->flush();

	        foreach ($this->daysOfWeek() as $day) {
                foreach ($data[$day] as $dayInterval) {
		            $this->persistBusinessHoursItem($businessHours, $day, $dayInterval["from"], $dayInterval["to"]);
	            }
            }
	    } catch (\Exception $ex) {
		    $this->logger->addCritical("Erro ao editar horário de funcionamento" . $ex->getMessage());
	    }
        $changes = [];
        $items = $businessHours->getItems();
        foreach ($items as $dayOfWeek => $interval) {
            $normalizedOld = array_map(function ($i) {
                return [
                    'from' => substr($i['from'], 0, 5),
                    'to' => substr($i['to'], 0, 5)
                ];
            }, $interval);

            $normalizedNew = array_map(function ($i) {
                return [
                    'from' => substr($i['from'], 0, 5),
                    'to' => substr($i['to'], 0, 5)
                ];
            }, $data[$dayOfWeek] ? $data[$dayOfWeek] : []);

            if ($normalizedOld != $normalizedNew) {
                $changes[$dayOfWeek] = [
                    "old" => $normalizedOld,
                    "new" => $normalizedNew,
                ];
            }
        }

        $this->auditLogService->createAuditLog(
            $businessHours->getRawItems()[0],
            Events::update()->getValue(),
            $changes,
            true
        );

	    return $businessHours;
    }

    private function persistBusinessHoursItem(BusinessHours $businessHours, $dayOfWeek, $startTime, $endTime)
    {
	    $item = new BusinessHoursItem();
	    $item->setBusinessHours($businessHours);
	    $item->setDayOfWeek($dayOfWeek);
	    $item->setStartTime($startTime);
	    $item->setEndTime($endTime);

	    $this->em->persist($item);
	    $this->em->flush();
    }

    private function daysOfWeek()
    {
    	return [
    		'monday',
		    'tuesday',
		    'wednesday',
		    'thursday',
		    'friday',
		    'saturday',
		    'sunday'
	    ];
    }

    /**
     * @param BusinessHours $businessHours
     */
    public function delete(BusinessHours $businessHours)
    {
    	$this->em->getRepository('DomainBundle:BusinessHours')->delete($businessHours);
        $this->auditLogService->createAuditLog(
            $businessHours,
            Events::delete()->getValue(),
            null,
            true
        );
    }

    /**
     * @param Nas $nas
     * @return DtoCheckAvailable
     */
    public function checkAvailable(Nas $nas = null)
    {
        if ($this->moduleService->checkModuleIsActive('business_hours')) {
            $client         = $this->getLoggedClient();
            $apMacAddress   = $nas->getAccessPointMacAddress();

            $accessPoint = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointByIdentifier($apMacAddress, $client);

            $accessPointId = ($accessPoint) ? $accessPoint[0]->getId() : null;

	        $businessHours = $this->em
		        ->getRepository('DomainBundle:BusinessHours')
		        ->getByAccessPoint($accessPointId);

            if (!$businessHours) {
                $businessHours = $this->em
                    ->getRepository('DomainBundle:BusinessHours')
                    ->getByAllAccessPoints($client);
            }

            if ($businessHours) {
                $validHours = $this->getHours($businessHours);

                return new DtoCheckAvailable(
                    $this->validatePeriod($businessHours, $accessPoint ? $accessPoint[0]->timezone : TimezoneService::DEFAULT_TIMEZONE),
                    $validHours
                );
            }
        }

        return new DtoCheckAvailable(true);
    }

    public function validatePeriod($businessHours, $apTimezone)
    {
	    $periods     = $this->getHours($businessHours);
        $now        = Carbon::now(new \DateTimeZone($apTimezone));
        $isValid = false;
        foreach($periods as $period) {
            $hourFrom   = Carbon::createFromTimeString($period['from'])
                ->setTimezone($apTimezone)
                ->setTimeFromTimeString($period['from']);
            $hourTo     = Carbon::createFromTimeString($period['to'])
                ->setTimezone($apTimezone)
                ->setTimeFromTimeString($period['to']);
			file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "hour now: " . $now . PHP_EOL, FILE_APPEND);
			file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "hour from: " . $hourFrom . PHP_EOL, FILE_APPEND);
			file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "hour to: " . $hourTo . PHP_EOL, FILE_APPEND);

            if($now->gte($hourFrom) && $now->lte($hourTo)) {
                $isValid = true;
            }
        }

	    return $isValid;
    }

    /**
	 * @param BusinessHours $businessHours
	 * @return mixed
	 */
    public function getHours(BusinessHours $businessHours)
    {
        $today = strtolower(date('l'));
	    return BusinessHoursHelper::getHours($businessHours, $today);
    }

	/**
	 * @param BusinessHours $businessHours
	 * @return mixed
	 */
    public function getHour(BusinessHours $businessHours)
    {
        $today = strtolower(date('l'));
	    return BusinessHoursHelper::getHours($businessHours, $today);
    }

	/**
	 * @param Client $client
	 * @param $id
	 * @param null $ap
	 * @return bool
	 */
    public function checkAccessPointAlreadyExists(Client $client, $id, $ap = null)
    {
        if ($ap) {
            if ($id) {
                return boolval($this->em
                    ->getRepository('DomainBundle:BusinessHours')
                    ->getByAccessPointAndBusinessHoursId($client, $ap->getId(), $id));
            }
            return boolval($this->em
                ->getRepository('DomainBundle:BusinessHours')
                ->getByAccessPoint($ap->getId()));
        }

        if ($id) {
            return boolval($this->em
                ->getRepository('DomainBundle:BusinessHours')
                ->getByAccessPointAndBusinessHoursId($client, false, $id));
        }

        return boolval(
            $this->em
	            ->getRepository('DomainBundle:BusinessHours')
	            ->getByAllAccessPoints($client)
        );
    }

	private function removeRelationships(BusinessHours $businessHours)
	{
		$this->em->getRepository('DomainBundle:BusinessHours')->removeRelationships($businessHours->getId());
	}
}