<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20240402174142 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE circuit_breaker (id INT AUTO_INCREMENT NOT NULL, service VARCHAR(40) NOT NULL, is_open TINYINT(1) DEFAULT \'0\' NOT NULL, failure_threshold INT DEFAULT 5 NOT NULL, failure_count INT DEFAULT 0 NOT NULL, last_failure_time DATETIME DEFAULT NULL, reset_timeout INT DEFAULT 300 NOT NULL, forced_open TINYINT(1) DEFAULT \'0\' NOT NULL, UNIQUE INDEX unique_service (service), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE timezone CHANGE dst dst CHAR(1)');
        $this->addSql('ALTER TABLE zone CHANGE country_code country_code CHAR(2)');
        $this->addSql('ALTER TABLE country CHANGE country_code country_code CHAR(2)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE circuit_breaker');
        $this->addSql('ALTER TABLE country CHANGE country_code country_code CHAR(2) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE timezone CHANGE dst dst CHAR(1) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE zone CHANGE country_code country_code CHAR(2) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
