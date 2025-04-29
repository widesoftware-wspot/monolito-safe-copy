<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200817124712 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE sms_credit_historic (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, quantity INT DEFAULT NULL, operation VARCHAR(15) DEFAULT NULL, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE sms_credit (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, total_available INT DEFAULT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("INSERT INTO modules (`shortcode`, `name`) VALUES ('sms_marketing', 'SMS Marketing');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE sms_credit_historic ");
        $this->addSql("DROP TABLE sms_credit ");
        $this->addSql("DELETE FROM modules WHERE `shortcode` = 'sms_marketing'");
    }
}
