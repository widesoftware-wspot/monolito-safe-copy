<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190301111050 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS `plan` (`id` int(11) NOT NULL AUTO_INCREMENT,`short_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,`plan` varchar(255) COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        $this->addSql("INSERT INTO plan (short_code, plan) VALUES('basic', 'Básico')");
        $this->addSql("INSERT INTO plan (short_code, plan) VALUES('pro', 'PRO')");
        $this->addSql("ALTER TABLE clients ADD  plan_id int(11)");
        $this->addSql("ALTER TABLE clients ADD CONSTRAINT id_fk_plan FOREIGN KEY (plan_id) REFERENCES plan(id)");
        $this->addSql("UPDATE clients SET plan_id = (SELECT id FROM plan WHERE short_code = 'pro') WHERE plan_id IS NULL");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
       $this->addSql("DROP TABLE plan");
    }
}
