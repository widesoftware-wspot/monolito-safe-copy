<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20240611191148 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE oauth_login ADD group_id INT DEFAULT NULL, ADD label_en VARCHAR(25) NOT NULL, ADD label_es VARCHAR(25) NOT NULL, ADD sso_type VARCHAR(255) NOT NULL, ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE oauth_login ADD CONSTRAINT FK_2D0B2101FE54D947 FOREIGN KEY (group_id) REFERENCES access_points_groups (id)');
        $this->addSql('CREATE INDEX IDX_2D0B2101FE54D947 ON oauth_login (group_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_name_domain ON oauth_login (name, domain)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE oauth_login DROP FOREIGN KEY FK_2D0B2101FE54D947');
        $this->addSql('DROP INDEX IDX_2D0B2101FE54D947 ON oauth_login');
        $this->addSql('DROP INDEX unique_name_domain ON oauth_login');
        $this->addSql('ALTER TABLE oauth_login DROP group_id, DROP label_en, DROP label_es, DROP sso_type, DROP name');
    }
}
