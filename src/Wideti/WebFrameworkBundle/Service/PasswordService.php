<?php

namespace Wideti\WebFrameworkBundle\Service;

use Doctrine\ORM\EntityManager;
use Twig_Environment;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Simple Password service, provide functionality for the most common
 * uses for password.
 *
 * Create New Password
 * Reset Password
 * Re-generate Password
 * Forgot my password
 *
 * @author Ramon Sanches <ramon.sanches@wideti.com.br>
 */
class PasswordService extends \SplFileInfo
{
    /**
     * @var EncoderFactory
     */
    protected $factory;

    /**
     * @var object Mailer
     */
    protected $mailer;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Dependency Injection
     *
     * @param EncoderFactory   $encoder
     * @param object           $mailer
     * @param EntityManager    $em
     * @param Twig_Environment $twig
     */
    public function __construct(EncoderFactory $encoder, $mailer, EntityManager $em, Twig_Environment $twig)
    {
        $this->factory = $encoder;
        $this->mailer  = $mailer;
        $this->em      = $em;
        $this->twig    = $twig;
    }

    /**
     * Generate a new random password for Generic User entity
     *
     * @param UserInterface $entity
     *
     * @return array
     */
    public function generate(UserInterface $entity)
    {
        $isStrong = false;
        $plainPassword = bin2hex(openssl_random_pseudo_bytes(4, $isStrong));
        if (!$isStrong) {
            throw new \RuntimeException('Insecure random bytes generated');
        }
        $encoder         = $this->factory->getEncoder($entity);

        $encodedPassword = $encoder->encodePassword(
            $plainPassword,
            $entity->getSalt()
        );

        return array(
            'plain'     => $plainPassword,
            'encoded'   => $encodedPassword,
        );
    }

    /**
     * Encode a given password string
     *
     * @param UserInterface $entity
     * @param string        $password
     *
     * @return string encoded password
     */
    public function encodePassword(UserInterface $entity, $password)
    {
        $encoder     = $this->factory->getEncoder($entity);

        $newPassword = $encoder->encodePassword(
            $password,
            $entity->getSalt()
        );

        return $newPassword;
    }

    /**
     * Handles with generate new password and re-send it to
     * the user
     *
     * @param UserInterface $entity
     *
     * @return mixed
     */
    public function forgot(UserInterface $entity)
    {
        /**
         * create new password
         */
        $newPassword = $this->generate($entity);
        /**
         * then update the entity
         */
        $entity->setPassword($newPassword['encoded']);
        $this->em->persist($entity);
        $this->em->flush();

        return $newPassword;
    }

    /**
     * Create an message object to return an error/success/notification
     *
     * @param string $type
     * @param string $message
     *
     * @return mixed
     */
    public function getMessage($type, $message)
    {
        $object             = new \StdClass();
        $object->type       = $type;
        $object->text       = $message;

        return $object;
    }
}
