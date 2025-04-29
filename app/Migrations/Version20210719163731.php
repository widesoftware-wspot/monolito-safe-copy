<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210719163731 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $this->addSql('ALTER TABLE usuarios ADD COLUMN two_factor_authentication_enabled INT DEFAULT 0 AFTER salt;');
        $this->addSql('ALTER TABLE usuarios ADD COLUMN two_factor_authentication_secret VARCHAR(100) DEFAULT NULL AFTER two_factor_authentication_enabled;');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE usuarios DROP COLUMN two_factor_authentication_enabled;');
        $this->addSql('ALTER TABLE usuarios DROP COLUMN two_factor_authentication_secret;');
    }
}
