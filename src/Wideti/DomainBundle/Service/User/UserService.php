<?php

namespace Wideti\DomainBundle\Service\User;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Repository\UsersRepository;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Erp\ErpService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\PasswordServiceAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Entity\UrlResetPassword;
use Wideti\DomainBundle\Helpers\PasswordGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class UserService
 * @package Wideti\DomainBundle\Service\User
 */
abstract class UserService
{
    use EntityManagerAware;
    use PasswordServiceAware;
    use TwigAware;
    use MailerServiceAware;
    use FlashMessageAware;
    use SecurityAware;
    use MailHeaderServiceAware;

    /**
     * @var ControllerHelper
     */
    private $controllerHelper;
    protected $whiteLabel;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var ErpService
     */
    private $erpService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var UsersRepository
     */
    private $usersRepository;

    /**
     * UserService constructor.
     * @param ConfigurationService $configurationService
     * @param ControllerHelper $controllerHelper
     * @param $whiteLabel
     * @param ErpService $erpService
     * @param CacheServiceImp $cacheService
     * @param ClientService $clientService
     * @param UsersRepository $usersRepository
     */
    public function __construct(
        ConfigurationService $configurationService,
        ControllerHelper $controllerHelper,
        $whiteLabel,
        ErpService $erpService,
        CacheServiceImp $cacheService,
        ClientService $clientService,
        UsersRepository $usersRepository
    ) {
        $this->controllerHelper      = $controllerHelper;
        $this->whiteLabel            = $whiteLabel;
        $this->configurationService  = $configurationService;
        $this->erpService            = $erpService;
        $this->cacheService          = $cacheService;
        $this->clientService         = $clientService;
        $this->usersRepository       = $usersRepository;
    }

    /**
     * @param Users $user
     * @param $autoPassword
     * @return Users
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function register(Users $user, $autoPassword)
    {
	    $plainTextPassword = $user->getPassword();
        
        if ($autoPassword) {
            $passwordGenerator  = new PasswordGenerator();
            $plainTextPassword  = $passwordGenerator->generate();
        }

        $financialManager = $user->getFinancialManager();

        if (is_null($financialManager)) {
            $user->setFinancialManager(false);
        }

        $user->setPlainPassword($plainTextPassword);
        $encodedPassword = $this->passwordService->encodePassword($user, $plainTextPassword);
        $deletedUser     = $this->getDeletedUser($user, $user->getClient());

        if ($deletedUser) {
            $user = $this->reactiveDeletedUser($deletedUser, $user, $encodedPassword);
        } else {
            $user->setPassword($encodedPassword);
        }

        $user->setTwoFactorAuthenticationEnabled(0);
       
        try {    
            $this->em->persist($user);
            $this->em->flush();
        } catch (DBALException $e) {    
            throw new DBALException($e->getMessage());
        }
        
        $url = $this->createUrlResetPassword($user, $user->getUserName());
        $this->prepareAndSendWelcomeEmailUrl($user, $plainTextPassword, base64_encode($user->getId().",".$url));
      
        if ($user->getFinancialManager() == true && $user->getUsername() != Users::USER_DEFAULT) {
            $this->erpService->addContact($user);
        }

        return $user;
    }

    /**
     * @param Users $user
     * @param $autoPassword
     * @return Users
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function registerByOauth(Users $user, $autoPassword)
    {
	    $plainTextPassword = $user->getPassword();
        
        if ($autoPassword) {
            $passwordGenerator  = new PasswordGenerator();
            $plainTextPassword  = $passwordGenerator->generate();
        }

        $financialManager = $user->getFinancialManager();

        if (is_null($financialManager)) {
            $user->setFinancialManager(false);
        }

        $user->setPlainPassword($plainTextPassword);
        $user->setCreatedAtOauth(1);
        $user->setCreatedAtIdp(0);
        $encodedPassword = $this->passwordService->encodePassword($user, $plainTextPassword);
        $user->setPassword($encodedPassword);
        $user->setTwoFactorAuthenticationEnabled(0);
       
        try {    
            $this->em->persist($user);
            $this->em->flush();
        } catch (DBALException $e) {    
            throw new DBALException($e->getMessage());
        }

        return $user;
    }

    /**
     * @param Users $user
     * @param $autoPassword
     * @return Users
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function registerByAutoLogin(Users $user, $autoPassword)
    {
	    $plainTextPassword = $user->getPassword();
        
        if ($autoPassword) {
            $passwordGenerator  = new PasswordGenerator();
            $plainTextPassword  = $passwordGenerator->generate();
        }

        $financialManager = $user->getFinancialManager();

        if (is_null($financialManager)) {
            $user->setFinancialManager(false);
        }

        $user->setPlainPassword($plainTextPassword);
        $user->setCreatedAtOauth(0);
        $user->setCreatedAtIdp(0);
        $encodedPassword = $this->passwordService->encodePassword($user, $plainTextPassword);
        $user->setPassword($encodedPassword);
        $user->setTwoFactorAuthenticationEnabled(0);
       
        try {    
            $this->em->persist($user);
            $this->em->flush();
        } catch (DBALException $e) {    
            throw new DBALException($e->getMessage());
        }

        return $user;
    }

    public function registerByAutoHiring(Users $user, $autoPassword, Client $client)
    {
        $plainTextPassword = $user->getPassword();

        if ($autoPassword) {
            $passwordGenerator  = new PasswordGenerator();
            $plainTextPassword  = $passwordGenerator->generate();
        }

        $financialManager = $user->getFinancialManager();

        if (is_null($financialManager)) {
            $user->setFinancialManager(false);
        }

        $user->setPlainPassword($plainTextPassword);
        $encodedPassword = $this->passwordService->encodePassword($user, $plainTextPassword);
        $deletedUser     = $this->getDeletedUser($user, $user->getClient());

        if ($deletedUser) {
            $user = $this->reactiveDeletedUser($deletedUser, $user, $encodedPassword);
        } else {
            $user->setPassword($encodedPassword);
        }

        $user->setTwoFactorAuthenticationEnabled(0);

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (DBALException $e) {
            throw new DBALException($e->getMessage());
        }

        $url = $this->createUrlResetPassword($user, $user->getUserName());
        $this->prepareAndSendWelcomeEmailUrlOnCreate($user, $plainTextPassword, base64_encode($user->getId().",".$url), $client);

        if ($user->getFinancialManager() == true && $user->getUsername() != Users::USER_DEFAULT) {
            $this->erpService->addContact($user);
        }

        return $user;
    }

    /**
     * @param Users $deletedUser
     * @param Users $user
     * @param $encodePassword
     * @return Users
     */
    public function reactiveDeletedUser(Users $deletedUser, Users $user, $encodePassword)
    {
        $deletedUser->setNome($user->getNome());
        $deletedUser->setRole($user->getRole());
        $deletedUser->setStatus($user->getStatus());
        $deletedUser->setFinancialManager($user->getFinancialManager());
        $deletedUser->setReceiveReportMail($user->getReceiveReportMail());
        $deletedUser->setReportMailLanguage($user->getReportMailLanguage());
        $deletedUser->setPassword($encodePassword);
        $deletedUser->setSalt($user->getSalt());
        $deletedUser->setDeletedAt(null);
        return $deletedUser;
    }

    /**
     * @param Users $user
     * @param Client $client
     * @return Users | null
     */
    public function getDeletedUser(Users $user, Client $client)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:Users')
            ->findOneBy([
                'username' => $user->getUsername(),
                'status' => Users::DELETED,
                'client' => $client
            ]);
    }

    /**
     * @param string $email
     * @return boolean
     */
    public function userExists($email, Client $client)
    {
        return $this
                ->em
                ->getRepository('DomainBundle:Users')
                ->exists($email, $client);
    }

    /**
     * @param $email
     * @return bool
     */
    public function checkUserEmailExists($email)
    {
        $checkEmail =  $this->em
                            ->getRepository('DomainBundle:Users')
                            ->findOneBy(['username' => $email]);
        return is_null($checkEmail);
    }

    /**
     * @param $email
     * @param Roles $role
     * @return bool
     */
    public function checkUserEmailAndRoleExists($email, Roles $role)
    {
        $check = $this->em
             ->getRepository('DomainBundle:Users')
             ->findOneBy([ 'username' => $email, 'role' => $role]);

        return is_null($check);
    }

	/**
	 * @param Users $user
	 * @param $password
	 * @return string
	 */
    private function generateUserLoginUrl(Users $user, $password)
    {
	    /**
	     * @var Client $client
	     */
        $client         = $user->getClient();
        $subdomain      = $client->getDomain();

        if ($password) {
            $loginUrlAdmin  = $this->controllerHelper->generateUrl(
                'login_admin',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } else {
            $loginUrlAdmin = $this->controllerHelper->generateUrl(
                'create_first_password',
                ['hash' => $user->getSalt()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $loginUrlAdmin  = str_replace('app.php/', '', $loginUrlAdmin);
        $loginUrlAdmin  = str_replace('app_dev.php/', '', $loginUrlAdmin);
        $urlArray       = explode(".", $loginUrlAdmin);
        $urlArray[0]    = "https://$subdomain";
        $url            = implode(".", $urlArray);

        if ($client->isWhiteLabel()) {
            $url = !$password? $loginUrlAdmin : "https://$subdomain/admin/login";
        }

        return $url;
    }

    /**
     * @param Users $user
     * @return Users
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(Users $user)
    {
        $uow            = $this->em->getUnitOfWork();
        $originalEntity = $uow->getOriginalEntityData($user);

        if ($originalEntity['financialManager'] == false && $user->getFinancialManager() == true) {
            $this->erpService->addContact($user);
        } elseif ($originalEntity['financialManager'] == true && $user->getFinancialManager() == false) {
            $this->erpService->removeContact($user);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param Users $user
     * @return Users
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(Users $user)
    {
        if ($this->getUser()->getUsername() == $user->getUsername()) {
            throw new \Exception('Você não pode se apagar do sistema.');
        }

        if ($user->getFinancialManager() == true) {
            $this->erpService->removeContact($user);
        }

        $user->setStatus(Users::DELETED);
        $user->setUltimoAcesso(null);
        $user->setDeletedAt(new \DateTime());
        $this->em->flush();

        return $user;
    }

    /**
     * @param Users $user
     * @return Users
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createPassword(Users $user)
    {
        $password = $this->passwordService->encodePassword($user, $user->getPassword());
        $user->setPlainPassword($user->getPassword());
        $user->setPassword($password);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param Users $user
     * @param $data
     * @param null $oldPassword
     * @return Users
     * @throws NoResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function changePassword(Users $user, $data, $oldPassword = null)
    {
        $idpPassword = null;

        if (!$data && $oldPassword) {
            $user->setPassword($oldPassword);
            $this->update($user);
            $user->setPlainPassword($oldPassword);
        } elseif (!$data && !$oldPassword) {
            $password = $this->resetPassword($user);
            $user->setPlainPassword($password['plain']);
        } else {
            $user->setPassword($this->passwordService->encodePassword($user, $data));
            $this->update($user);
            $user->setPlainPassword($data);
        }

        return $user;
    }

    public function validateStrongPassword($password)
    {
        if (strlen($password) < 8) {
            return false;
        } 
        if (strtolower($password) == $password || strtoupper($password) == $password) {
            return false;
        }
        if (preg_match('/\d+/', $password) == 0) {
            return false;
        }

        if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $password) == 0) {
            return false;
        }

        return true;
    }

    /**
     * @param Users $user
     * @param $name
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeName(Users $user, $name)
    {
        $user->setNome($name);
        $this->update($user);
    }

    /**
     * @param Users $user
     * @param $receiveReportMail
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeReceiveReportMail(Users $user, $receiveReportMail)
    {
        $user->setReceiveReportMail($receiveReportMail);
        $this->update($user);
    }

    /**
     * @param Users|null $user
     * @throws NoResultException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function resetPassword(Users $user = null, $isPanel = false)
    {
        if (!$user instanceof Users) {
            throw new NoResultException();
        }

        $password = $this->passwordService->forgot($user);
        $this->mailRecoveryPassword($user, $password['plain'], $isPanel);

        return $password;
    }

    /**
     * @param $emailOrUsername
     * @throws NoResultException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function forgotPassword($emailOrUsername)
    {
        $client = $this->session->get("wspotClient");
        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->findOneBy(
                [
                    'username' => $emailOrUsername,
                    'client' => $client->getId(),
                    'createdAtOauth' => 0,
                    'erpId' => null
                ]
            );
        if (is_null($user)) {
            throw new NoResultException();
        } 

        $url = $this->createUrlResetPassword($user, $emailOrUsername);
        $this->mailUrlRecoveryPassword($user, base64_encode($user->getId().",".$url));        
    }

    public function createUrlResetPassword($user, $emailOrUsername)
    {
        $url = new UrlResetPassword(); 
        if (!is_null($user->getUrlsResetPassword())) {
            $url = $user->getUrlsResetPassword();
        }
        
        $url->setUser($user);
        $url->setUrl(md5($emailOrUsername . date("YmdHis")));
        $url->setExpiredByUse(false);
        $url->setCreatedAt(new \DateTime());
        $this->em->persist($url);
        $this->em->flush();
        
        return $url;
    }

    /**
     * @param $emailOrUsername
     * @throws NoResultException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function forgotUserPanelPassword($emailOrUsername)
    {
        $user = $this->em
            ->getRepository('DomainBundle:Users')
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.username = :username')
            ->andWhere('u.client is NULL')
            ->setParameter('username', $emailOrUsername)
            ->getQuery()
            ->getOneOrNullResult();

        try {
            $this->resetPassword($user,true);
        } catch (NoResultException $e) {
            throw new NoResultException();
        }
    }

    /**
     * @param Users $user
     * @param $password
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function mailRecoveryPassword(Users $user, $password, $isPanel = false)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        /** @var Client $client */
        $client = $this->session->get('wspotClient');
        $domain = $isPanel ? '' : $client->getDomain();
        $urlPainel = $isPanel ? $this->controllerHelper->generateUrl(
            'redirect_panel_login',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) : $this->controllerHelper->generateUrl(
            'login_admin',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $config = $this->configurationService->getByIdentifierOrDefault('', $client);
        $this->configurationService->setOnSession($this->configurationService->getCacheKey('admin'), $config);
        $this->controllerHelper->setTwigGlobalVariable('config', $config);
        $subject = 'Mambo WiFi - Alteração de senha';

        $fromEmail = $client->getEmailSenderDefault();
        if ($client->isWhiteLabel()) {
            $companyName = isset($this->whiteLabel['companyName']) ? $this->whiteLabel['companyName'] : '';
            $subject = $companyName != ''? "{$companyName} - Alteração de senha":"Hotspot WiFi - Alteração de senha";
        }



        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($subject)
            ->from(["Troca de Senha - Admin" => $fromEmail])
            ->to([
                [$this->clearInputForSendEmail($user->getNome()) => $user->getUsername()]
            ])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Users:emailSenha.html.twig',
                    [
                        'password' => $password,
                        'user'     => $user->getNome(),
                        'email'    => $user->getUsername(),
                        'domain'   => $domain,
                        'painel'   => $urlPainel,
                        'isWhiteLabel' => $client->isWhiteLabel(),
                        'from_email' => $fromEmail,
                    ]
                )
            )
            ->build()
        ;

        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }

    /**
     * @param Users $user
     * @param $plainTextPassword
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function prepareAndSendWelcomeEmail(Users $user, $plainTextPassword)
    {
        if ($user->getUsername() != Users::USER_DEFAULT) {
            $passwordUrlAdmin = $this->generateUserLoginUrl($user, $plainTextPassword);
            $this->sendWelcomeMail($user, $plainTextPassword, $passwordUrlAdmin);
        }
    }

    public function prepareAndSendWelcomeEmailUrl(Users $user, $plainTextPassword, $urlPath)
    {
        if ($user->getUsername() != Users::USER_DEFAULT) {
            $passwordUrlAdmin = $this->generateUserLoginUrl($user, $plainTextPassword);
            $this->sendWelcomeMailUrl($user, $plainTextPassword, $passwordUrlAdmin, $urlPath);
        }
    }

    public function prepareAndSendWelcomeEmailUrlOnCreate(Users $user, $plainTextPassword, $urlPath, $client)
    {
        if ($user->getUsername() != Users::USER_DEFAULT) {
            $passwordUrlAdmin = $this->generateUserLoginUrl($user, $plainTextPassword);
            $this->sendWelcomeMailUrlOnCreate($user, $plainTextPassword, $passwordUrlAdmin, $urlPath, $client);
        }
    }

    /**
     * @param Users $user
     * @param $password
     * @param $passwordUrlAdmin
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function sendWelcomeMail(Users $user, $password, $passwordUrlAdmin)
    {
        $companyName = isset($this->whiteLabel['companyName']) ? $this->whiteLabel['companyName'] : '';
        $builder     = new MailMessageBuilder();
        /** @var Client $client */
        $client = $this->session->get('wspotClient');
        $domain = $client->getDomain();

        $fromEmail = $client->getEmailSenderDefault();
        $subject = 'Mambo WiFi - Cadastro de novo administrador';

        if ($client->isWhiteLabel()) {
            $subject = $companyName != '' ? "{$companyName} - Cadastro de novo administrador" : "Hotspot WiFi - Cadastro de novo administrador";

        }

        $message = $builder
            ->subject($subject)
            ->from(['Novo admin cadastro' => $fromEmail])
            ->to([
                [$this->clearInputForSendEmail($user->getNome()) => $user->getUsername()]
            ])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Users:emailBemVindo.html.twig',
                    [
                        'password'              => $password,
                        'user'                  => $user->getNome(),
                        'email'                 => $user->getUsername(),
                        'role'                  => $user->getRole()->getName(),
                        'password_url_admin'    => $passwordUrlAdmin,
                        'domain'                => $domain,
                        'isWhiteLabel'          => $client->isWhiteLabel(),
                        'whiteLabel'            => $this->whiteLabel
                    ]
                )
            )
            ->build()
        ;

        if ($fromEmail) {
            $builder->replyTo($fromEmail);
        }

        $this->mailerService->send($message);
    }

    /**
     * @param Users $user
     * @param $password
     * @param $passwordUrlAdmin
     * @param $urlPath
     * @param $domain
     * @param Client $client
     * @return void
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function sendWelcomeMailUrlOnCreate(Users $user, $password, $passwordUrlAdmin, $urlPath, $client=null)
    {

        $urlResetPassword = $this->controllerHelper->generateUrl(
                'create_first_password',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . "?url=".$urlPath;

        if (is_null($client)) {
            /** @var Client $client */
            $client = $this->session->get('wspotClient');
        }

        /**
         * @var WhiteLabel $wl
         */
        $wl = $this->em->getRepository("DomainBundle:WhiteLabel")->findOneBy(['client' => $client]);

        $companyName = !is_null($wl->getCompanyName()) ? $wl->getCompanyName() : '';
        $builder     = new MailMessageBuilder();

        $domain = $client->getDomain();

        $userDomain = $user->getClient()->getDomain();

        $urlArray       = explode(".", $urlResetPassword);
        $urlArray[0]    = "https://$userDomain";
        $url            = implode(".", $urlArray);

        if ($user->getClient()->isWhiteLabel()) {
            $urlArray       = explode("/", $urlResetPassword);
            $urlArray[2]    = $userDomain;
            $url   = implode("/", $urlArray);
        }

        $fromEmail = $client->getEmailSenderDefault();
        $subject = 'Mambo WiFi - Cadastro de novo administrador';

        if ($client->isWhiteLabel()) {
            $subject = "Hotspot WiFi - Cadastro de novo administrador";
        }


        $message = $builder
            ->subject($subject)
            ->from(['Novo admin cadastro' => $fromEmail])
            ->to([
                [$this->clearInputForSendEmail($user->getNome()) => $user->getUsername()]
            ])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Users:emailUrlBemVindo.html.twig',
                    [
                        'password'              => $password,
                        'user'                  => $user->getNome(),
                        'email'                 => $user->getUsername(),
                        'role'                  => $user->getRole()->getName(),
                        'password_url_admin'    => $passwordUrlAdmin,
                        'domain'                => $domain,
                        'isWhiteLabel'          => $client->isWhiteLabel(),
                        'whiteLabel'            => $wl,
                        'url_path'              => $url
                    ]
                )
            )
            ->build();
        if ($fromEmail) {
            $builder->replyTo($fromEmail);
        }

        $this->mailerService->send($message);
    }

    private function sendWelcomeMailUrl(Users $user, $password, $passwordUrlAdmin, $urlPath)
    {
       
        $urlResetPassword = $this->controllerHelper->generateUrl(
            'create_first_password',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) . "?url=".$urlPath;
        
        $companyName = isset($this->whiteLabel['companyName']) ? $this->whiteLabel['companyName'] : '';
        $builder     = new MailMessageBuilder();
        /** @var Client $client */
        $client = $this->session->get('wspotClient');
        $domain = $client->getDomain();

        $fromEmail = $client->getEmailSenderDefault();
        $subject = 'Mambo WiFi - Cadastro de novo administrador';
        
        if ($client->isWhiteLabel()) {
            $subject = $companyName != '' ? "{$companyName} - Cadastro de novo administrador" : "Hotspot WiFi - Cadastro de novo administrador";
        }

        
        $message = $builder
            ->subject($subject)
            ->from(['Novo admin cadastro' => $fromEmail])
            ->to([
                [$this->clearInputForSendEmail($user->getNome()) => $user->getUsername()]
            ])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Users:emailUrlBemVindo.html.twig',
                    [
                        'password'              => $password,
                        'user'                  => $user->getNome(),
                        'email'                 => $user->getUsername(),
                        'role'                  => $user->getRole()->getName(),
                        'password_url_admin'    => $passwordUrlAdmin,
                        'domain'                => $domain,
                        'isWhiteLabel'          => $client->isWhiteLabel(),
                        'whiteLabel'            => $this->whiteLabel,
                        'url_path'              => $urlResetPassword
                    ]
                )
            )
            ->build();       
        if ($fromEmail) {
            $builder->replyTo($fromEmail);
        }

        $this->mailerService->send($message);
    }

    /**
     * @param Request $request
     * @param $form
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    public function createAdminUserByBluePanel(Request $request, $form)
    {
        $user = new Users();
        $user->setUsername($request->get('admin_email'));
        $user->setNome($request->get('admin_name'));
        $roleId = (int)$request->get('admin_profile_role');
        $role = $this->getRoleById($roleId);
        $user->setRole($role);
        $statusId = (int)$request->get('admin_status');
        $status = $this->getStatusById($statusId);
        $user->setStatus($status);
        $domain = $form->getData()->getDomain();
        $user->setReceiveReportMail((int)$request->get('admin_receive_report_mail'));
        $client = $this->clientService->getClientByDomain($domain);
        $user->setClient($client);
        $user->setDataCadastro(new \DateTime());
        $user->setReportMailLanguage(0);
        $user->setFinancialManager(0);
        $user->setTwoFactorAuthenticationEnabled(0);
        $password = $this->passwordService->generate($user);
        $user->setPassword($password['encoded']);
        $this->save($user);
        $url = $this->createUrlResetPassword($user, $user->getUserName());
        $this->prepareAndSendWelcomeEmailUrlOnCreate($user, $password['plain'], base64_encode($user->getId().",".$url), $client);


        $user->setPlainPassword($password['plain']);
        return $user;
    }

    /**
     * @param $id
     * @return null|object|\Wideti\DomainBundle\Entity\Roles
     */
    public function getRoleById($id)
    {
        return $this->em->getRepository('DomainBundle:Roles')->find($id);
    }

    /**
     * @param $id
     * @return int
     */
    public function getStatusById($id) {
        switch ($id) {
            case 0:
                return Users::INACTIVE;
                break;
            case 1:
                return Users::ACTIVE;
                break;
            case 2:
                return Users::DELETED;
                break;
        }
    }

    /**
     * @param $input
     * @return mixed
     */
    private function clearInputForSendEmail($input) {
        $search = [",", ";"];
        $replace = "";
        $input = str_replace($search, $replace, $input);
        return $input;
    }

    /**
     * @param $status
     * @param $role
     * @return int
     */
    public function countUsers($status, $role)
    {
        return count($this->em->getRepository('DomainBundle:Users')->findBy([
            'status'  => $status,
            'role'    => $role
        ]));
    }

    /**
     * @param Users $user
     * @return Users
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createUserOnPanel(Users $user)
    {
        $password = $user->getPassword();
        $this->setPanelUserAttributes($user);
        $this->save($user);

        $user->setPlainPassword($password);
        return $user;
    }

    /**
     * @param Users $user
     */
    private function setPanelUserAttributes(Users $user)
    {
        $user->setDataCadastro(new \DateTime());
        $user->setPassword($this->passwordService->encodePassword($user, $user->getPassword()));
        $user->setCreatedAtIdp(true);
        $user->setTwoFactorAuthenticationEnabled(false);
    }

    /**
     * @param Users $user
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Users $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function markAsCreatedAtIdp(Users $user)
    {
        /*
         * Só estamos marcando como criado os usuários que não são MANAGER.
         * MANAGER são os nossos usuários do WSpot que com o mesmo e-mail acessam qualquer painel.
         * Esses usuários não possuem vínculo com clientes, por esse motivo não podemos setar como criado no IDP.
         * A cada painel que fazemos login, criará um novo usuário no IDP.
         */
        if ($user->getRole()->getName() != Roles::MANAGER) {
            $user->setCreatedAtIdp(true);
            $this->save($user);
        }
    }

     /**
     * @param Users $user
     * @param $password
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function mailUrlRecoveryPassword(Users $user, $urlPath, $isPanel = false)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        /** @var Client $client */
        $client = $this->session->get('wspotClient');
        $domain = $isPanel ? '' : $client->getDomain();
        $urlPainel = $isPanel ? $this->controllerHelper->generateUrl(
            'redirect_panel_login',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) : $this->controllerHelper->generateUrl(
            'login_admin',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $urlResetPassword = $this->controllerHelper->generateUrl(
            'reset_forgotten_password',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) . "?url=".$urlPath;
        $config = $this->configurationService->getByIdentifierOrDefault('', $client);
        $this->configurationService->setOnSession($this->configurationService->getCacheKey('admin'), $config);
        $this->controllerHelper->setTwigGlobalVariable('config', $config);
        $subject = 'Mambo WiFi - Alteração de senha';

        $isWhiteLabel = false;
        $fromEmail = $client->getEmailSenderDefault();
        if ($client->isWhiteLabel()) {
            $isWhiteLabel = true;
            $companyName = isset($this->whiteLabel['companyName']) ? $this->whiteLabel['companyName'] : '';
            $subject = $companyName != ''? "{$companyName} - Alteração de senha":"Hotspot WiFi - Alteração de senha";
        }

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($subject)
            ->from(["Troca de Senha - Admin" => $fromEmail])
            ->to([
                [$this->clearInputForSendEmail($user->getNome()) => $user->getUsername()]
            ])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Users:emailUrlSenha.html.twig',
                    [
                        'url' => $urlResetPassword,
                        'user'     => $user->getNome(),
                        'email'    => $user->getUsername(),
                        'domain'   => $domain,
                        'painel'   => $urlPainel,
                        'isWhiteLabel' => $isWhiteLabel,
                        'from_email' => $fromEmail,
                    ]
                )
            )
            ->build()
        ;

        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }

    public function resetForgottenPassword(Users $user, $data, $urlPath, $oldPassword = null) {
        $urlResetPassword = $user->getUrlsResetPassword();
        $currentTime = new \DateTime();            
        $elapsedTime = $currentTime->getTimestamp() - $urlResetPassword->getCreatedAt()->getTimestamp();
        
        if($urlPath != $urlResetPassword->getUrl() || $urlResetPassword->getExpiredByUse() || $elapsedTime > 300) {
            return false;
        }
        $this->changePassword($user, $data, $oldPassword);
        $urlResetPassword->setExpiredByUse(true);
        $this->em->persist($urlResetPassword);
        $this->em->flush();

        $user->setResetedToStrongPassword(true);
        $this->em->persist($user);
        $this->em->flush();
        return true;
    }

    public function createFirstPassword(Users $user, $data, $urlPath, $oldPassword = null) 
    {
        $urlResetPassword = $user->getUrlsResetPassword();
        if (!$urlResetPassword) {
            $urlResetPassword = new UrlResetPassword();
            $urlResetPassword->setUser($user);
            $urlResetPassword->setUrl(md5($user->getUsername() . date("YmdHis")));
            $urlResetPassword->setCreatedAt(new \DateTime());
        }
        $this->createPassword($user);
        $urlResetPassword->setExpiredByUse(true);
        $this->em->persist($urlResetPassword);
        $this->em->flush();

        $user->setResetedToStrongPassword(true);
        $this->em->persist($user);
        $this->em->flush();
        return true;
    }

    public function resetPasswordToStrong(Users $user, $data, $oldPassword = null) {
        $urlResetPassword = $user->getUrlsResetPassword();
        if (!$urlResetPassword) {
            $urlResetPassword = new UrlResetPassword();
            $urlResetPassword->setUser($user);
            $urlResetPassword->setUrl(md5($user->getUsername() . date("YmdHis")));
            $urlResetPassword->setCreatedAt(new \DateTime());
        }
        $this->changePassword($user, $data, $oldPassword);
        $urlResetPassword->setExpiredByUse(true);
        $this->em->persist($urlResetPassword);
        $this->em->flush();

        $user->setResetedToStrongPassword(true);
        $this->em->persist($user);
        $this->em->flush();
        return true;
    }

    public function resetPasswordIsValid(Users $user, $urlPath)
    {
        $urlResetPassword = $user->getUrlsResetPassword();
        if (!$urlResetPassword) {
            $urlResetPassword = new UrlResetPassword();
            $urlResetPassword->setUser($user);
            $urlResetPassword->setUrl(md5($user->getUsername() . date("YmdHis")));
            $urlResetPassword->setCreatedAt(new \DateTime());
        }
        $currentTime = new \DateTime();
        $elapsedTime = $currentTime->getTimestamp() - $urlResetPassword->getCreatedAt()->getTimestamp();

        if($urlPath != $urlResetPassword->getUrl() || $urlResetPassword->getExpiredByUse() || $elapsedTime > 300) {
            return false;
        }

        return true;
    }

    public function resetUrlIsValid(Users $user, $urlPath)
    {
        $urlResetPassword = $user->getUrlsResetPassword();

        if($urlPath != $urlResetPassword->getUrl() || $urlResetPassword->getExpiredByUse() ) {
            return false;
        }

        return true;
    }

    public function currentPasswordIsValid($user, $oldPassword, $passwordInput)
    {
        $encodedPasswordInput = $this->passwordService->encodePassword($user, $passwordInput);
        return $oldPassword == $encodedPasswordInput;
    }

    public function requestEditUserPassword(Users $user)
    {
        $url = $this->createUrlResetPassword($user, $user->getUsername());

        $this->mailUrlEditPassword($user, base64_encode($user->getId().",".$url));

    }

    private function mailUrlEditPassword(Users $user, $urlPath, $isPanel = false)
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        /** @var Client $client */
        $client = $this->session->get('wspotClient');
        $domain = $isPanel ? '' : $client->getDomain();
        $urlPainel = $isPanel ? $this->controllerHelper->generateUrl(
            'redirect_panel_login',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) : $this->controllerHelper->generateUrl(
            'login_admin',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $urlResetPassword = $this->controllerHelper->generateUrl(
                'reset_user_password',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . "?url=".$urlPath;

        $config = $this->configurationService->getByIdentifierOrDefault('', $client);
        $this->configurationService->setOnSession($this->configurationService->getCacheKey('admin'), $config);
        $this->controllerHelper->setTwigGlobalVariable('config', $config);
        $subject = 'Mambo WiFi - Alteração de senha';

        $fromEmail = $client->getEmailSenderDefault();
        if ($client->isWhiteLabel()) {
            $companyName = isset($this->whiteLabel['companyName']) ? $this->whiteLabel['companyName'] : '';
            $subject = $companyName != ''? "{$companyName} - Alteração de senha":"Hotspot WiFi - Alteração de senha";

        }

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($subject)
            ->from(["Troca de Senha - Admin" => $fromEmail])
            ->to([
                [$this->clearInputForSendEmail($user->getNome()) => $user->getUsername()]
            ])
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Users:emailUrlEditSenha.html.twig',
                    [
                        'url' => $urlResetPassword,
                        'user'     => $user->getNome(),
                        'email'    => $user->getUsername(),
                        'domain'   => $domain,
                        'painel'   => $urlPainel,
                        'isWhiteLabel' => $client->isWhiteLabel(),
                        'from_email' => $fromEmail,
                    ]
                )
            )
            ->build()
        ;

        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }
}