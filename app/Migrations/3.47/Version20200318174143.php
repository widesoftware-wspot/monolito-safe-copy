<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200318174143 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE devices (mac_address VARCHAR(255) NOT NULL, os VARCHAR(255) NOT NULL, platform VARCHAR(255) NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(mac_address)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE devices_entries (id INT AUTO_INCREMENT NOT NULL, mac_address VARCHAR(255) DEFAULT NULL, guest_id INT DEFAULT NULL, client_id INT DEFAULT NULL, created DATETIME NOT NULL, last_access DATETIME NOT NULL, INDEX IDX_D5795A4FB728E969 (mac_address), INDEX IDX_D5795A4F9A4AA658 (guest_id), INDEX IDX_D5795A4F19EB6921 (client_id), UNIQUE INDEX unique_references_ids (mac_address, guest_id, client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("ALTER TABLE devices_entries ADD CONSTRAINT FK_D5795A4FB728E969 FOREIGN KEY (mac_address) REFERENCES devices (mac_address);");
        $this->addSql("ALTER TABLE devices_entries ADD CONSTRAINT FK_D5795A4F9A4AA658 FOREIGN KEY (guest_id) REFERENCES visitantes (id);");
        $this->addSql("ALTER TABLE devices_entries ADD CONSTRAINT FK_D5795A4F19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE devices_entries;");
        $this->addSql("DROP TABLE devices;");
    }
}
