<?php

namespace Wideti\DomainBundle\Service\Vendor;

use Doctrine\ODM\MongoDB\DocumentManager;
use mysql_xdevapi\Exception;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\VendorRepository;

class VendorService
{
    /**
     * @var VendorRepository
     */
    private $vendorRepository;
    /**
     * @var AccessPointsRepository
     */
    private $acccessPointsRepository;

    /**
     * VendorService constructor.
     * @param VendorRepository $vendorRepository
     * @param AccessPointsRepository $acccessPointsRepository
     */
    public function __construct(VendorRepository $vendorRepository, AccessPointsRepository $acccessPointsRepository)
    {
        $this->vendorRepository = $vendorRepository;
        $this->acccessPointsRepository = $acccessPointsRepository;
    }

    public function getVendors()
    {
        $vendors = $this->vendorRepository->findAllVendors();
        return $vendors;
    }

    public function getVendorsAsList($lowerCase = false)
    {
        $vendors = $this->getVendors();
        if (!$vendors) return [];

        return array_map(function ($vendor) use ($lowerCase) {
            return $lowerCase
                ? strtolower($vendor->getVendor())
                : $vendor->getVendor();
        }, $vendors);
    }

    public function hasMask($vendor)
    {
        if (!$vendor) return false;
        $search = $this->vendorRepository->findOneBy([
            'vendor' => $vendor
        ]);
        return $search ? boolval($search->getMask()) : false;
    }

    public function getVendorsToView()
    {
        $vendors = [];
        foreach ($this->getVendors() as $data) {
            if (array_key_exists($data->getVendor(), vendor::VENDOR_MAP_BY_DISPLAY_NAME)) {
                $vendorLower = vendor::VENDOR_MAP_BY_DISPLAY_NAME[$data->getVendor()];
                $vendors[$vendorLower] = [
                    'manual' => $data->getManual(),
                    'mask'   => $data->getMask()
                ];
            }          
        }
        return $vendors;
    }

    /**
     * @return array
     */
    public function getVendorsNameWithMacAddressMask()
    {
        $allVendors = $this->getVendors();
        $vendorsWithMask = [];
        foreach ($allVendors as $vendor) {
            if (!empty($vendor->getMask())) {
                array_push($vendorsWithMask, strtolower($vendor->getVendor()));
            }
        }
        return $vendorsWithMask;
    }

    /**
     * @return array
     */
    public function getVendorsNameWithoutMacAddressMask()
    {
        $allVendors = $this->getVendors();
        $vendorsWithoutMask = [];
        foreach ($allVendors as $vendor) {
            if (empty($vendor->getMask())) {
                array_push($vendorsWithoutMask, strtolower($vendor->getVendor()));
            }
        }
        return $vendorsWithoutMask;
    }

    /**
     * @return array
     */
    public function getAllVendorsName()
    {
        $allVendors = $this->getVendors();
        $allVendorsArray = [];
        foreach ($allVendors as $vendor) {
            array_push($allVendorsArray, strtolower($vendor->getVendor()));
        }
        return $allVendorsArray;
    }

    /**
     * @return Object | null
     */
    public function getVendorByName ($vendorName)
    {
        $vendor = $this->vendorRepository->findOneBy(['vendor'=>$vendorName]);
        return $vendor;
    }

    /**
     * @return Object | null
     */
    public function getVendorByNameAndClient ($calledStationName, $client)
    {
        $ap = $this->acccessPointsRepository->findOneBy(['friendlyName'=>$calledStationName, 'client'=>$client]);

        if (!$ap) {
            return null;
        }
        $vendor = $this->getVendorByName($ap->getVendor());
        return $vendor;
    }

    public function getVendorByCalledStationIdAndClient($calledStationId, $client)
    {
        $ap = $this->acccessPointsRepository->findOneBy(['identifier'=>$calledStationId, 'client'=>$client]);
        if(!$ap){
            return null;
        }

        return $ap->getVendorId();
    }
}
