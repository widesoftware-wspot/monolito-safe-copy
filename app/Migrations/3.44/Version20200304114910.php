<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200304114910 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE client_configurations SET value = 365 WHERE value = "sempre"');
        $this->addSql('UPDATE configurations SET params = \'{"choices":{"5":"5 dias","15":"15 dias","30":"30 dias","180":"6 meses","365":"1 ano"}}\' WHERE `key` = "auto_login_days"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
