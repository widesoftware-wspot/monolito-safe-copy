<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200130164703 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `radius_wspotv3`.`campaign` ADD COLUMN `pre_login_video` VARCHAR(255) NULL DEFAULT NULL AFTER `pre_login_image_time`, ADD COLUMN `pos_login_video` VARCHAR(255) NULL DEFAULT NULL AFTER `pos_login_image_time`;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `radius_wspotv3`.`campaign` DROP COLUMN `pos_login_video`, DROP COLUMN `pre_login_video`;");
    }
}
