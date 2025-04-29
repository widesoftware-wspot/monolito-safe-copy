<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20240624132930 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE controllers_unifi(id INT NOT NULL AUTO_INCREMENT,address VARCHAR(100) NOT NULL,port INT NOT NULL DEFAULT 8443,username VARCHAR(50) NOT NULL,password VARCHAR(50) NOT NULL,is_mambo BOOL NOT NULL DEFAULT FALSE,is_active BOOL NOT NULL DEFAULT TRUE,comments TEXT,CONSTRAINT PK_CONTROLLERS_UNIFI PRIMARY KEY(id)); DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB;");
        $this->addSql("CREATE TABLE clients_controllers_unifi(id INT NOT NULL AUTO_INCREMENT,client_id INT NOT NULL,unifi_id INT NOT NULL,CONSTRAINT PK_CLIENTS_CONTROLLERS_UNIFI PRIMARY KEY(id),CONSTRAINT UK_CLIENTS_CONTROLLERS_UNIFI UNIQUE KEY(client_id,unifi_id),CONSTRAINT FK_CLIENTS_CONTROLLERS_UNIFI_CLIENTS FOREIGN KEY(client_id)REFERENCES clients(id)ON DELETE CASCADE,CONSTRAINT FK_CLIENTS_CONTROLLERS_UNIFI_CONTROLLERS FOREIGN KEY(unifi_id)REFERENCES controllers_unifi(id)ON DELETE CASCADE); DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE clients_controllers_unifi;");
        $this->addSql("DROP TABLE controllers_unifi;");
    }
}
