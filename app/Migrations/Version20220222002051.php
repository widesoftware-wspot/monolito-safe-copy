<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220222002051 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE user_token_auth (user_id INT NOT NULL, token TEXT NOT NULL, PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("ALTER TABLE user_token_auth ADD CONSTRAINT FK_3DBD43D7A76ED395 FOREIGN KEY (user_id) REFERENCES usuarios (id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE user_token_auth DROP FOREIGN KEY FK_3DBD43D7A76ED395;");
        $this->addSql("DROP TABLE user_token_auth;");

    }
}
