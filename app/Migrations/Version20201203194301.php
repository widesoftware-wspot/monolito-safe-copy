<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20201203194301 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE data_controller_agent (
                                id VARCHAR(255) NOT NULL, 
                                client_id INT DEFAULT NULL, 
                                full_name VARCHAR(250) NOT NULL, 
                                cpf VARCHAR(50) NOT NULL, 
                                phone_number VARCHAR(50) NOT NULL, 
                                email VARCHAR(250) NOT NULL, 
                                job_occupation VARCHAR(150) NOT NULL, 
                                birthday DATE NOT NULL, 
                                UNIQUE INDEX UNIQ_D075596F19EB6921 (client_id), 
                                PRIMARY KEY(id) ) 
                                DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('ALTER TABLE data_controller_agent ADD CONSTRAINT FK_D075596F19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE data_controller_agent;');
    }
}
