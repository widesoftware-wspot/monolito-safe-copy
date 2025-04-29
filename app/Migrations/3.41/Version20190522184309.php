<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190522184309 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
	    $this->addSql("CREATE TABLE segmentation (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, status VARCHAR(50) NOT NULL, title VARCHAR(255) DEFAULT NULL, filter LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
