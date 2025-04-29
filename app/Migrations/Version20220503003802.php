<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220503003802 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $json = '{"pt_br":"Gênero","en":"Gender","es":"Género"}';
        $choices = '{"pt_br":{"Gênero":"","Masculino":"Masculino","Feminino":"Feminino","Outros":"Outros","Prefiro não responder":"Prefiro não responder"},"en":{"Gender":""," Male":" Male"," Female":" Female"," Others ":" Others ","Prefer not to answer":"Prefer not to answer" },"es":{"Género":"","Masculino":"Masculino","Femenino":"Femenino","Hombre":"Hombre","Prefiero no decir":"Prefiero no decir"}}';

        $this->addSql("UPDATE custom_fields_template SET name = '$json'  WHERE  identifier = 'gender';");

        $this->addSql("UPDATE custom_fields_template SET choices = '$choices' WHERE identifier = 'gender';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
