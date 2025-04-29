<?php

namespace Wideti\DomainBundle\Tests\Service\Vendor;

class VendorTestHelper
{
    public static function getVendorArray()
    {
        return [
            [
                'vendor' => 'Aerohive',
                'manual' => 'https://docs.google.com/presentation/d/1a76Ly1AtYc9dbX3XCmtF27Dits9yU-AG8vOkqsUjIaE/embed?start=false&loop=false&delayms=6000000',
                'mask' => 'HH-HH-HH-HH-HH-HH'
            ],
            [
                'vendor' => 'Aruba',
                'manual' => 'https://docs.google.com/presentation/d/1nOvCmP0-OYC2iLvOF0KXyKOQD4n0m5CdaF8Y0woGgzA/embed?start=false&loop=false&delayms=60000',
                'mask' => 'HH-HH-HH-HH-HH-HH'
            ],
            [
                'vendor' => 'Cisco',
                'manual' => 'https://docs.google.com/presentation/d/1JGSenAEASojg5yWqUju50mLWsW5ZTqRiVhZPYsO2VY4/embed?start=false&loop=false&delayms=60000',
                'mask' => 'HH-HH-HH-HH-HH-HH'
            ],
            [
                'vendor' => 'Mikrotik',
                'manual' => 'https://docs.google.com/presentation/d/1nRIU0OatIK7E3qJ3w9UO9SzVpCRZ35jI8G5NtxKoUZI/embed?start=false&loop=false&delayms=60000',
                'mask' => ''
            ],
            [
                'vendor' => 'PfSense',
                'manual' => 'https://docs.google.com/presentation/d/1t0a_zj-MJRquwYsZYHYH66sMpAfeCvtbnlQ3kLklv9c/embed?start=false&loop=false&delayms=60000',
                'mask' => ''
            ],
            [
                'vendor' => 'Ruckus-Controlador',
                'manual' => 'https://docs.google.com/presentation/d/1EWhYCkcls4cTTL7gAhIXAoSPcZYZIAiYB6F46s0YeUM/embed?start=false&loop=false&delayms=60000',
                'mask' => 'HH-HH-HH-HH-HH-HH'
            ],
            [
                'vendor' => 'Ruckus-Standalone',
                'manual' => 'https://docs.google.com/presentation/d/1xQ6o9bvTbnPhFp_YvGvKrJZvcOsh9ACHqbykmzws3wA/embed?start=false&loop=false&delayms=60000',
                'mask' => ''
            ],
            [
                'vendor' => 'ZyXEL',
                'manual' => 'https://docs.google.com/presentation/d/1-trDOn8EmNKoxKm72oDE_xGLRARUoDXfc5_5rP_ToS4/embed?start=false&loop=false&delayms=60000',
                'mask' => 'HH-HH-HH-HH-HH-HH'
            ],
        ];
    }

    public static function getVendorsAsList()
    {
        return [
            'aerohive',
            'aruba',
            'cisco',
            'mikrotik',
            'pfsense',
            'ruckus-controlador',
            'ruckus-standalone',
            'zyxel'];
    }

}