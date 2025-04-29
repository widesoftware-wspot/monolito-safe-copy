<?php


namespace Wideti\DomainBundle\Analytics\Events;


class EventType
{
    /**
     * Events from captive portal
     */
    CONST AUTO_LOGIN_ACCESS = "form-auto-login-access";
    CONST FORM_SIGNIN_ACCESS = "form-signin-access";
    CONST PREVIEW_ACCESS = "preview-page-access";
    CONST AGREEMENT_TERM = "agreement-page-access";
    CONST UPDATE_EMAIL = "update-email-page-access";
    CONST CONFIRMATION_SIGNUP = "confirmation-signup-page-access";
    CONST WAITING_CONFIRMATION_SIGNUP = "waiting-confirmation-signup-page-access";
    CONST COMPLETE_REGISTRATION = "complete-registration-page-access";
    CONST PUBLISH_ACTION = "publish-actions-page-access";
    CONST SIGN_UP_ACCESS_CODE = "sign-up-access-code-page-access";
    CONST HAPVIDA_TWO_FACTOR = "hapvida-two-factor-page-access";
    CONST UPDATE_GUEST_DATA = "update-guest-data-page-access";
    CONST GOOGLE_LOGIN = "execute-google-sign-process";
    CONST SURVEY_LOGIN = "execute-survey-process";
    CONST TWITTER_LOGIN = "execute-twitter-sign-process";
    CONST LINKEDIN_LOGIN = "execute-linkedin-sign-process";
    CONST FACEBOOK_LOGIN = "execute-facebook-sign-process";
    CONST FACEBOOK_PUBLISH_ACTION = "facebook-publish-action";
    CONST MAC_LOGIN_SIGN_IN = "sign-in-using-mac";
    CONST LOGIN_SUBMITTED_TO_RADIUS = "login-submitted-to-radius";
    CONST AUTOLOGIN_CLICK = "autologin-click";
    CONST LOGIN_BY_FORM = "login-by-form";
    CONST REGISTER_BY_FORM = "register-by-form";
    CONST JAVASCRIPT_DISABLED = "view-js-disabled";

    /**
     * Campaign Views
     */
    CONST VIEW_PRE_LOGIN_ACTION = "view-pre-login-page";
    CONST VIEW_POS_LOGIN_ACTION = "view-pos-login-page";

    /**
     * Block Events
     */
    CONST BLOCK_BY_TIME = "user-blocked-by-time";
    CONST INACTIVE_GUEST = "guest-inactive";
    CONST BAD_PARAMETER_AP = "bad-parameter-ap";
    CONST NONEXISTENT_AP = "nonexistent-ap";
    CONST INACTIVE_AP = "inactive-ap";
    CONST BLACKLISTED_DEVICE = "device-in-blacklist";
    CONST OUT_OF_BUSINESS_HOUR = "out-of-business-hour";
    CONST VALIDITY_ACCESS_HAS_EXPIRED = "validity-access-has-expired";

}
