<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20211206140304 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO radius_wspotv3.custom_fields_template (identifier,name,`type`,validations,visible_for_clients) VALUES ('custom_internet_provider','{\"pt_br\":\"Qual seu provedor?\",\"en\":\"Who is your provider?\",\"es\":\"¿Cuál es tu proveedor?\"}','text','[{\"type\":\"required\",\"value\":true,\"message\":\"wspot.signup_page.field_required\",\"locale\":[\"pt_br\",\"en\",\"es\"]}]','[\"globalinternet\"]');");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM radius_wspotv3.custom_fields_template WHERE identifier = 'custom_internet_provider'");
    }
}
