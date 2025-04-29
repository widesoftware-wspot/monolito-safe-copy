<?php

namespace Wideti\DomainBundle\Service\ApiWSpot\Helpers;

use Symfony\Component\Debug\Exception\ContextErrorException;
use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\ApiWSpotResources;

class GetResourceNamesFromTokens
{
    /**
     * @param array $tokens
     * @return array
     */
    public static function getAsString(array $tokens)
    {
        $names = [];

        /**
         * @var ApiWSpot $token
         */
        foreach ($tokens as $token) {
            $resourcesPermissions = $token->getResources()->toArray();

            $resources = [];

            foreach ($resourcesPermissions as $resource) {
                array_push($resources, $resource->getResource());
            }

            $resourceNames = array_map(function($obj){
                $flipValues = array_flip(ApiWSpotResources::getResources());
                try {
                    return $flipValues[$obj];
                } catch (ContextErrorException $ex) {
                    $flipValues['clients'] = 'ERP Superlógica - Clientes';
                    $flipValues['internal_access_points'] = 'Cadastro de Pontos de acesso pelo Script do suporte';
                    $flipValues['internal_sms'] = 'Monitoramento do callback de envio de SMS';
                    $flipValues['internal_clients'] = 'Identificação do cliente pelo armazenamento';
                    $flipValues['internal_consent'] = 'Revogação do consentimento do visitante';
                    return $flipValues[$obj];
                }
            }, array_unique($resources));

            $names[$token->getToken()] = join(", ", $resourceNames);
        }

        return $names;
    }
}
