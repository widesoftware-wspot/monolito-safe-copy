<?php


namespace Wideti\DomainBundle\Analytics\Events;


class EventIdentifier
{
    /**
     * Events from captive portal
     */
    CONST AUTO_LOGIN_ACCESS = "Acessou o formulário de auto-login";
    CONST ACCESS_LOGIN_PAGE = "Acessou a página de login";
    CONST PREVIEW_ACCESS = "Acessou a página de preview";
    CONST CAMPAIGN_CREATE_NEW = "Acessou página de criação de campanha";
    CONST CAMPAIGN_CREATE_EDIT = "Acessou página de edição de campanha";
    CONST CAMPAIGN_CREATE_DELETE = "Acessou página de edição de campanha";
    CONST CAMPAIGN_PRE_LOGIN_VIEW = "Visualizou a campanha pré-login";
    CONST CAMPAIGN_POS_LOGIN_VIEW = "Visualizou a campanha pós-login";
    CONST AGREEMENT_TERM_VIEW = "Visualizou o termo de consentimento";
    CONST UPDATE_EMAIL = "Visualizou a página de atualização de email";
    CONST CONFIRMATION_SIGNUP = "Visualizou a página de confirmação de cadastro";
    CONST WAITING_CONFIRMATION_SIGNUP = "Visualizou a página de aguardando confirmação de cadastro";
    CONST COMPLETE_REGISTRATION = "Visualizou a página de complemento de cadastro";
    CONST PUBLISH_ACTION = "Visualizou a página de compartilhamento de redes sociais";
    CONST SIGN_UP_ACCESS_CODE = "Visualizou a página de código de acesso ";
    CONST HAPVIDA_TWO_FACTOR = "Visualizou a página de autenticação em dois fatores do hapvida ";
    CONST UPDATE_GUEST_DATA = "Visualizou a página de atualização de dados do vistante";
    CONST GOOGLE_LOGIN = "Realizou o cadastro/login usando o Google";
    CONST TWITTER_LOGIN = "Realizou o cadastro/login usando o Twitter";
    CONST FACEBOOK_LOGIN = "Realizou o cadastro/login usando o Facebook";
    CONST SURVEY_LOGIN = "Respondeu pesquisa";
    CONST FACEBOOK_PUBLISH_ACTION = "Visualizou a página de publicação do Facebook";
    CONST LINKEDIN_LOGIN = "Realizou o cadastro/login usando o LinkedIn";
    CONST LINKEDIN_PUBLISH_ACTION = "Visualizou a página de publicação do LinkedIn";
    CONST MAC_LOGIN_SIGN_IN = "Realizou o login usando o MAC";
    CONST LOGIN_SUBMITTED_TO_RADIUS = "Realizou o login pela aplicação e enviou o formulário para o Radius";
    CONST AUTOLOGIN_CLICK = "Clicou no auto-login";
    CONST LOGIN_BY_FORM = "Realizou o login pelo formulário";
    CONST REGISTER_BY_FORM = "Realizou o cadastro pelo formulário";
    CONST JAVASCRIPT_DISABLED = "Visualizou a página de javascript desabilitado";
    /**
     * Campaign Views
     */
    CONST VIEW_PRE_LOGIN_ACTION = "Visualizou a campanha pré-login";
    CONST VIEW_POS_LOGIN_ACTION = "Visualizou a campanha pós-login";

    /**
     * Block events
     */
    CONST BLOCK_BY_TIME = "Visualizou a tela de bloquio por tempo";
    CONST INACTIVE_GUEST = "Visualizou a tela de visitante inativo";
    CONST BAD_PARAMETER_AP = "Visualizou a tela de ap mal configurada";
    CONST NONEXISTENT_AP = "Visualizou a tela de ap não cadastrada";
    CONST INACTIVE_AP = "Visualizou a tela de ap inativa";
    CONST BLACKLISTED_DEVICE = "Visualizou a tela de dispositivo bloqueado pela blacklist";
    CONST OUT_OF_BUSINESS_HOUR = "Visualizou a tela de fora do horário de funcionamento";
    CONST VALIDITY_ACCESS_HAS_EXPIRED = "Visualizou a página de acesso expirada";

}
