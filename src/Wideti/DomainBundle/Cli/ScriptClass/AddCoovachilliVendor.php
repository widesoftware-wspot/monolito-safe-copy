<?php

namespace Wideti\DomainBundle\Cli\ScriptClass;

use Wideti\DomainBundle\Cli\AbstractScript;

/**
 * Class AddCoovachilliVendor
 * @package Wideti\DomainBundle\Cli\ScriptClass
 */
class AddCoovachilliVendor extends AbstractScript
{
    /**
     * @return mixed
     */
    public function run()
    {
        try {
            $vendor = ["vendor" => "Coovachilli", "manual" => "", "mask"   => ""];
            $this->documentManager->getConnection()->getMongoClient()->wspot_system->vendors->insert($vendor);
            $this->output->writeln("Processamento efetuado com sucesso");
        } catch (\Exception $exception) {
            $this->output->writeln("Ocorreram problemas no processamento: {$exception->getMessage()}");
        }
    }
}