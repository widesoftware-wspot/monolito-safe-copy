<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20240409181606 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE oauth_login ADD customizeGuestGroup VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE timezone CHANGE dst dst CHAR(1)');
        $this->addSql('ALTER TABLE country CHANGE country_code country_code CHAR(2)');
        $this->addSql('ALTER TABLE zone CHANGE country_code country_code CHAR(2)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE country CHANGE country_code country_code CHAR(2) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE oauth_login DROP customizeGuestGroup');
        $this->addSql('ALTER TABLE timezone CHANGE dst dst CHAR(1) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE zone CHANGE country_code country_code CHAR(2) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
