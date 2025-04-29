<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration usada para criar e popular a tabela de Reserved Domain
 */
class Version20190504184516 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE reserved_domains (`id` INT NOT NULL AUTO_INCREMENT,`domain` VARCHAR(50) NOT NULL, PRIMARY KEY (`id`))");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('http')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('httpwww')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('https')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('httpswww')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('www')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('ftp')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('wspot_system')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('wspot')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('wideti')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('homolog')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('teste')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('test')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('pre-prod')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('prod')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('demo')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('dev')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('suporte')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('support')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('internal')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('contratacao')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('purchase')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('poc')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('api')");
        $this->addSql("INSERT INTO reserved_domains (`domain`) VALUES ('admin')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE reserved_domains");
    }
}
