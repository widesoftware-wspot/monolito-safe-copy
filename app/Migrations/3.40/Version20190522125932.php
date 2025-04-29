<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190522125932 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
	    $this->addSql("CREATE TABLE api_rdstation (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, enable_auto_integration TINYINT(1) DEFAULT '0' NOT NULL, in_access_points INT NOT NULL, created DATETIME NOT NULL, INDEX IDX_137CD30819EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
	    $this->addSql("CREATE TABLE api_rdstation_access_points (api_rdstation_id INT NOT NULL, access_point_id INT NOT NULL, INDEX IDX_4B4655149BF43F50 (api_rdstation_id), INDEX IDX_4B465514AD3D0F93 (access_point_id), PRIMARY KEY(api_rdstation_id, access_point_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
	    $this->addSql("ALTER TABLE api_rdstation ADD CONSTRAINT FK_137CD30819EB6921 FOREIGN KEY (client_id) REFERENCES clients (id);");
	    $this->addSql("ALTER TABLE api_rdstation_access_points ADD CONSTRAINT FK_4B4655149BF43F50 FOREIGN KEY (api_rdstation_id) REFERENCES api_rdstation (id);");
	    $this->addSql("ALTER TABLE api_rdstation_access_points ADD CONSTRAINT FK_4B465514AD3D0F93 FOREIGN KEY (access_point_id) REFERENCES access_points (id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
