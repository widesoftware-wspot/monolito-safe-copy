<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200415184137 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devices_entries 
                            ADD COLUMN last_ap_identifier VARCHAR(255) DEFAULT NULL,
                            ADD COLUMN last_ap_friendly_name VARCHAR(255) DEFAULT NULL,
                            ADD COLUMN timezone VARCHAR(55) DEFAULT NULL
                    ;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE devices_entries 
                            DROP COLUMN last_ap_identifier,
                            DROP COLUMN last_ap_friendly_name,
                            DROP COLUMN timezone
                    ;');
    }
}
