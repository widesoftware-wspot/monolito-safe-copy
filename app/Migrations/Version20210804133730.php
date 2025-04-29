<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210804133730 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table clients add column is_white_label boolean default false;");
        $this->addSql("UPDATE clients SET is_white_label = 1 WHERE id = 14838");
        $this->addSql("UPDATE clients SET is_white_label = 1 WHERE id = 14886");
        $this->addSql("UPDATE clients SET is_white_label = 1 WHERE id = 14961");
        $this->addSql("UPDATE clients SET is_white_label = 1 WHERE id = 14964");


    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table clients drop column  is_white_label;");
    }
}
