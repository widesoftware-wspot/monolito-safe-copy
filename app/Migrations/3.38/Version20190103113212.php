<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190103113212 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
	    $this->addSql("CREATE TABLE wspot_logger (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, module VARCHAR(100) DEFAULT NULL, action VARCHAR(100) DEFAULT NULL, date DATETIME NOT NULL, PRIMARY KEY(id));");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE wspot_logger;");
    }
}
