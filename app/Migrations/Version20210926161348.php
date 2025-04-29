<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210926161348 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO radius_wspotv3.vendor (vendor, manual, mask, router_mode) VALUES('Unifi Ubiquiti', 'https://suporte.mambowifi.com/pt-BR/support/solutions/articles/16000125342-configura%C3%A7%C3%A3o-ubiquiti-unifi-com-wspot ', 'HH-HH-HH-HH-HH-HH', 'router');");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM radius_wspotv3.vendor WHERE vendor = 'UniFi Ubiquiti';");

    }
}
