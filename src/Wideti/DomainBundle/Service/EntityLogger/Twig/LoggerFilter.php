<?php
namespace Wideti\DomainBundle\Service\EntityLogger\Twig;

use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;

class LoggerFilter extends \Twig_Extension
{
    use TranslatorAware;
    use RouterAware;
    use MongoAware;

    private $controllerHelper;

    public function __construct(FrontendControllerHelper $controllerHelper)
    {
        $this->controllerHelper = $controllerHelper;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('el_description', [$this, 'describeChange']),
            new \Twig_SimpleFilter('el_module', [$this, 'describeModule'])
        ];
    }

    public function describeModule($module)
    {
        switch ($module) {
            case 'Users':
                $middle = "Administradores";
                break;
            case 'Campaign':
                $middle = "Campanhas";
                break;
            case 'Guest':
                $middle = 'Visitantes';
                break;
            case 'Guests':
                $middle = 'Visitantes';
                break;
            case 'Configuration':
                $middle = 'Configurações';
                break;
            default:
                $middle = 'Desconhecido';
                break;
        }
        return $middle;
    }

    public function describeChange(array $change)
    {
        $action = "{action}";
        $middle = "";
        $fields = "";
        $object = (array_key_exists('id', $change['changeset'])) ? $change['changeset']['id'] : null;

        if ($change['action'] == 'create') {
            $action = "Criou";
            $fields = $this->describeFields($change);
        }

        if ($change['action'] == 'update') {
            $action = "Alterou";
            $fields = $this->describeFields($change);
        }

        if ($change['action'] == 'delete') {
            $action = "Removeu";
            if ($change['module'] == 'Guests') {
                $fields = $this->describeFields($change);
            }
        }

        switch ($change['module']) {
            case 'Users':
                $id     = "#{$object}";
                $email  = (isset($change['changeset']['email'])) ? $change['changeset']['email'] : $id;
                $aux    = ($change['action'] == 'update') ? "do" : "o";
                if (isset($object)) {
                    $middle = $aux . " administrador <a href='".$this->controllerHelper->generateUrl('admin_usuarios_edit', ['id'=>$object])."' target='_blank'>".$email."</a>";
                } else {
                    $middle = $aux . " administrador " . $email;
                }
                break;
            case 'Campaign':
                $id         = "#{$object}";
                $campaign   = (isset($change['changeset']['campaign'])) ? $change['changeset']['campaign'] : $id;
                $aux        = ($change['action'] == 'update') ? "da" : "a";
                if (isset($object)) {
                    $middle = $aux . " campanha <a href='".$this->controllerHelper->generateUrl('campaign_edit', ['id'=>$object])."' target='_blank'>".$campaign."</a>";
                } else {
                    $middle = $aux . " campanha " . $campaign;
                }
                break;
            case 'Guest':
                $id     = "#{$object}";
                $guest  = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findOneBy([
                        'mysql' => $object
                    ]);
                $loginField = $guest->getProperties()[$guest->getLoginField()];
                $aux    = ($change['action'] == 'update') ? "do" : "o";
                if (isset($object)) {
                    $middle = $aux . " visitante <a href='".$this->controllerHelper->generateUrl('admin_visitantes_edit', ['id'=>$object])."' target='_blank'>".$loginField."</a>";
                } else {
                    $middle = $aux . " visitante " . $id;
                }
                break;
            case 'Configuration':
                $middle = "";
                break;
        }
        $description = "{$action} {$fields} {$middle}";

        return $description;
    }

    public function describeFields(array $record)
    {
        if ($record['module'] == 'Guests' && ($record['action'] == 'create' || $record['action'] == 'delete')) {
            return 'um visitante';
        }

        if (!isset($record['changeset']['changes']) && count($record['changeset']['changes']) == 0) {
            return '';
        }

        $fields = [];
        $end    = " ";

        if ($record['action'] !== 'create') {
            foreach ($record['changeset']['changes'] as $field => $changes) {
                if (!is_array($changes[1])) {
                    $translate      = 'entity.' . $record['module'] . '.' . $field;
                    $whereChanged   = ' para ' . $changes[1];

                    if ($record['module'] == 'Configuration' && isset($record['changeset']['field'])) {
                        if ($changes[1] == '') {
                            $changes[1] = 0;
                        }
                        if ($changes[0] == '') {
                            $changes[0] = 0;
                        }
                        $whereChanged = " \"{$record['changeset']['field']}\" de " . $changes[0] . ' para '. $changes[1];
                        $end = "";
                    }
                    $fields[$field] = $this->translator->trans($translate) . $whereChanged;
                }
            }
        }

        return implode(", ", $fields) . $end;
    }

    public function getName()
    {
        return 'entity_logger';
    }
}
