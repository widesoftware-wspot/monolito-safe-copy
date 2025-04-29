<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220711173241 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE oauth_login ADD COLUMN client_secret VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE oauth_login ADD COLUMN field_login VARCHAR(255) DEFAULT NULL");
        $this->addSql("INSERT INTO radius_wspotv3.oauth_login(domain, client_id, resource, url, label, client_secret, field_login)
        VALUES ('dev', 'f73136d3-0f5f-4262-8191-e371b71ce84f', 'https://graph.windows.net', 'https://login.microsoftonline.com/02c7f4b6-492a-4a85-a390-bb6127fe41f0/oauth2', 'Azure', 'azure-secret', 'email')");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM radius_wspotv3.oauth_login WHERE domain = 'dev'");
        $this->addSql("ALTER TABLE oauth_login DROP COLUMN client_secret");
        $this->addSql("ALTER TABLE oauth_login DROP COLUMN field_login");
    }
}
