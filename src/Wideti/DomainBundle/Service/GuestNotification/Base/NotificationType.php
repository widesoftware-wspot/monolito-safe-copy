<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Base;

class NotificationType
{
    const REGISTER              = 'wspot.notification.register';
    const CONFIRMATION          = 'wspot.notification.confirmation';
    const RESEND_CONFIRMATION   = 'wspot.notification.resend_confirmation';
    const PASSWORD              = 'wspot.notification.password';
}
