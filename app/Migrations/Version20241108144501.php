<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20241108144501 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE admin_oauth_login (id INT AUTO_INCREMENT NOT NULL, erp_id INT NOT NULL, client_id VARCHAR(255) NOT NULL, client_secret VARCHAR(255) DEFAULT NULL, resource VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, authorize_url VARCHAR(255) NOT NULL, token_url VARCHAR(255) NOT NULL, `label` VARCHAR(25) NOT NULL, sso_type VARCHAR(255) DEFAULT \'ad\' NOT NULL, field_login VARCHAR(255) NOT NULL, field_name VARCHAR(255) NOT NULL, scope VARCHAR(255) DEFAULT \'openid\' NOT NULL, token_type VARCHAR(255) DEFAULT \'id_token\' NOT NULL, name VARCHAR(255) DEFAULT NULL, roles_identifiers LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE usuarios ADD created_at_oauth TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE admin_oauth_login');
        $this->addSql('ALTER TABLE usuarios DROP created_at_oauth');
    }
}
