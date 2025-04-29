<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200317114057 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE client_configurations SET value = \"https://www.google.com\" WHERE configuration_id = 4 AND value = \"http://www.wspot.com.br\" AND client_id != 10990;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE client_configurations SET value = \"http://www.wspot.com.br\" WHERE configuration_id = 4 AND value = \"https://www.google.com\" AND client_id != 10990;");
    }
}
