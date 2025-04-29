<?php


namespace Wideti\DomainBundle\Service\AuditLogs\EventTypes;


class Events
{
    /**
     * @return EventType
     */
    public static function create() {
        return EventCreate::event();
    }

    /**
     * @return EventType
     */
    public static function accept() {
        return EventAccept::event();
    }

    /**
     * @return EventType
     */
    public static function active() {
        return EventActive::event();
    }

    /**
     * @return EventType
     */
    public static function delete() {
        return EventDelete::event();
    }

    /**
     * @return EventType
     */
    public static function export() {
        return EventExport::event();
    }

    /**
     * @return EventType
     */
    public static function import() {
        return EventImport::event();
    }

    /**
     * @return EventType
     */
    public static function inactive() {
        return EventInactive::event();
    }

    /**
     * @return EventType
     */
    public static function move() {
        return EventMove::event();
    }

    /**
     * @return EventType
     */
    public static function receive() {
        return EventReceive::event();
    }

    /**
     * @return EventType
     */
    public static function send() {
        return EventSend::event();
    }

    /**
     * @return EventType
     */
    public static function signIn() {
        return EventSignIn::event();
    }

    /**
     * @return EventType
     */
    public static function signOut() {
        return EventSignOut::event();
    }

    /**
     * @return EventType
     */
    public static function update() {
        return EventUpdate::event();
    }

    /**
     * @return EventType
     */
    public static function view() {
        return EventView::event();
    }

    public static function confirm() {
        return EventConfirm::event();
    }
}
