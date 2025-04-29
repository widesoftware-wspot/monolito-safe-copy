<?php


namespace Wideti\DomainBundle\Service\AuditLogs\Kinds;


class Kinds
{
    /**
     * @return Kind
     */
    public static function accessCode() {
        return KindAccessCode::kind();
    }

    /**
     * @return Kind
     */
    public static function accessPoint() {
        return KindAccessPoint::kind();
    }

    /**
     * @return Kind
     */
    public static function accessPointGroup() {
        return KindAccessPointGroup::kind();
    }

    /**
     * @return Kind
     */
    public static function dataController() {
        return KindDataController::kind();
    }

    /**
     * @return Kind
     */
    public static function apiToken() {
        return KindApiToken::kind();
    }

    /**
     * @return Kind
     */
    public static function automaticContracting() {
        return KindAutomaticContracting::kind();
    }

    /**
     * @return Kind
     */
    public static function campaign() {
        return KindCampaign::kind();
    }

    /**
     * @return Kind
     */
    public static function client() {
        return KindClient::kind();
    }

    /**
     * @return Kind
     */
    public static function businessHours() {
        return KindBusinessHours::kind();
    }

    /**
     * @return Kind
     */
    public static function deviceEntry() {
        return KindDeviceEntry::kind();
    }

    /**
     * @return Kind
     */
    public static function deviceLock() {
        return KindDeviceLock::kind();
    }

    /**
     * @return Kind
     */
    public static function egoi() {
        return KindEGoi::kind();
    }

    /**
     * @return Kind
     */
    public static function guest() {
        return KindGuest::kind();
    }

    /**
     * @return Kind
     */
    public static function group() {
        return KindGroup::kind();
    }

    /**
     * @return Kind
     */
    public static function configuration() {
        return KindConfiguration::kind();
    }

    /**
     * @return Kind
     */
    public static function configurationValue() {
        return KindConfigurationValue::kind();
    }

    /**
     * @return Kind
     */
    public static function rdStation() {
        return KindRDStation::kind();
    }

    /**
     * @return Kind
     */
    public static function smsMarketing() {
        return KindSmsMarketing::kind();
    }

    /**
     * @return Kind
     */
    public static function system() {
        return KindSystem::kind();
    }

    /**
     * @return Kind
     */
    public static function template() {
        return KindTemplate::kind();
    }

    /**
     * @return Kind
     */
    public static function userAdmin() {
        return KindUserAdmin::kind();
    }

    /**
     * @return Kind
     */
    public static function segmentation() {
        return KindSegmentation::kind();
    }

    /**
     * @return Kind
     */
    public static function whitelabel() {
        return KindWhiteLabel::kind();
    }

    /**
     * @return Kind
     */
    public static function guestListReport() {
        return KindGuestListReport::kind();
    }

    /**
     * @return Kind
     */
    public static function onlineGuestReport() {
        return KindOnlineGuestReport::kind();
    }

    /**
     * @return Kind
     */
    public static function accessHistoricReport()
    {
        return KindAccessHistoricReport::kind();
    }

    /**
     * @return Kind
     */
    public static function callToActionReport()
    {
        return KindCallToActionReport::kind();
    }

    /**
     * @return Kind
     */
    public static function guestsReport()
    {
        return KindGuestsReport::kind();
    }

    /**
     * @return Kind
     */
    public static function clientConfiguration() {
        return KindClientConfigurations::kind();
    }
}
