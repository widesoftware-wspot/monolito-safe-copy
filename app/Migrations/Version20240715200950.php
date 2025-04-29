<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20240715200950 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE custom_fields_template CHANGE identifier identifier VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_identifier_visibleforclients ON custom_fields_template (identifier, visible_for_clients(255))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX unique_identifier_visibleforclients ON custom_fields_template');
        $this->addSql('ALTER TABLE custom_fields_template CHANGE identifier identifier VARCHAR(50) NOT NULL COLLATE utf8_unicode_ci');
    }
}
