<?php
namespace Wideti\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceImp;
use Wideti\DomainBundle\Service\Configuration\Dto\ConfigurationDto;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Helpers\FieldsHelper;

class ConfigType extends AbstractType
{
    use SessionAware;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var ConfigurationDto $config
         */
        $config                 = $options['data'];
        /**
         * @var Client $client
         */
        $client                 = $this->getLoggedClient();
        $options                = $config->getParams();
        $options['label']       = false;
        $options['constraints'] = [];

        if (isset($config->getParams()['constraints'])) {
            $options['constraints'] = $this->createFieldValidation(
                $config->getParams()['constraints']
            );
        }

        if ($client->getNoRegisterFields() != 0 && array_key_exists($config->getKey(),ConfigurationServiceImp::CONFIGS_NO_REGISTER_FIELDS)) {
            $options['disabled'] = true;
        }

        if ($config->getKey() == "login_form" && !$client->isEnablePasswordAuthentication()){
            $options['disabled'] = true;
            $config->setValue(false);
        }
        $builder->add('value', FieldsHelper::fieldTypesMap[$config->getType()], $options);
    }

    private function createFieldValidation(array $constraints)
    {
        $classes = [];

        foreach ($constraints as $constraint => $data) {
            $classes[] = $this->createConstraint($constraint, $data);
        }
        return $classes;
    }

    private function createConstraint($class, $data)
    {
        $camelizedClass  = str_replace('_', '', ucwords($class));
        $constraintClass = "Symfony\\Component\\Validator\\Constraints\\" . $camelizedClass;

        if (!is_array($data)) {
            $data = [];
        }
        return new $constraintClass($data);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ConfigurationDto::class
        ]);
    }

    public function getBlockPrefix()
    {
        return 'config';
    }
}
