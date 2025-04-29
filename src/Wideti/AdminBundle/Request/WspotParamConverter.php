<?php
namespace Wideti\AdminBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\AdminBundle\Exception\HttpException;
use Wideti\AdminBundle\Exception\NotFoundException;
use Wideti\AdminBundle\Exception\ObjectNotFoundException;

class WspotParamConverter implements ParamConverterInterface
{
    use SessionAware;
    use MongoAware;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry = null)
    {
        $this->registry = $registry;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $name    = $configuration->getName();
        $class   = $configuration->getClass();
        $options = $configuration->getOptions();
        $id      = $request->get("id");

        if ($class == "DomainBundle:Guests") {
            if (is_numeric($id)) {
                $entity = $this->em
                    ->getRepository($class)
                    ->findOneBy([
                        'client' => $this->session->get('wspotClient'),
                        'id'     => $request->get('id')
                    ])
                ;

                if (null === $entity || empty($entity)) {
                    throw new ObjectNotFoundException($options['message']);
                }

                $object = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findOneBy([
                        "mysql" => $entity->getId()
                    ])
                ;

                if (null === $object || empty($object)) {
                    throw new ObjectNotFoundException($options['message']);
                }
                $request->attributes->set($name, $object);
                return $object;
            }

            $object = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->find($id)
            ;
            if (null === $object || empty($object)) {
                throw new ObjectNotFoundException($options['message']);
            }
            $request->attributes->set($name, $object);
            return $object;

        } else {
            $object = $this->em
                ->getRepository($class)
                ->findOneBy([
                    'client' => $this->session->get('wspotClient'),
                    'id'     => $request->get('id')
                ])
            ;
            if (null === $object || empty($object)) {
                throw new ObjectNotFoundException($options['message']);
            }
            $request->attributes->set($name, $object);

            return $object;
        }
    }

    public function supports(ParamConverter $configuration)
    {
        if (!$configuration instanceof ParamConverter) {
            return false;
        }

        if (null === $configuration->getClass()) {
            return false;
        }

        $options  = $this->getOptions($configuration);
        $this->em = $this->getManager($options['entity_manager'], $configuration->getClass());

        return true;
    }

    protected function getOptions(ConfigurationInterface $configuration)
    {
        return array_replace([
            'entity_manager' => null,
            'exclude'        => [],
            'mapping'        => [],
            'strip_null'     => false
        ], $configuration->getOptions());
    }

    private function getManager($name, $class)
    {
        if (null === $name) {
            return $this->registry->getManagerForClass($class);
        }

        return $this->registry->getManager($name);
    }
}
