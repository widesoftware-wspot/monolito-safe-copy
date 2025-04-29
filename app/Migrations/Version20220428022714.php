<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220428022714 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE url_reset_password (id INT NOT NULL AUTO_INCREMENT, user_id INT NOT NULL, url TEXT NOT NULL, expired_by_use TINYINT(1), created_at DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE(user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("ALTER TABLE url_reset_password ADD CONSTRAINT FK_USUARIOS_ID FOREIGN KEY (user_id) REFERENCES usuarios (id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE url_reset_password DROP FOREIGN KEY FK_USUARIOS_ID;");
        $this->addSql("DROP TABLE url_reset_password;");
    }
}
