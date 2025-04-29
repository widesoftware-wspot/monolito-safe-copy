<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210719151934 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
    	$this->addSql("
    		alter table usuarios add column spot_manager boolean default false after idp_id;
    	");

    	$this->addSql("
    	create table spots_users (
				client_id int,
				user_id int,
				primary key (client_id, user_id),
				foreign key (client_id) references clients (id),
				foreign key (user_id) references usuarios (id)
			);
    	");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    	$this->addSql("drop table spots_users;");
    	$this->addSql("alter table usuarios drop column spot_manager;");
    }
}
