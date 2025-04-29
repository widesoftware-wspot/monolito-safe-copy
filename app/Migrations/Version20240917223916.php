<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20240917223916 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Adicionando as colunas image_desktop2, image_desktop3, image_mobile2 e image_mobile3 na tabela campaign_media_image
        $this->addSql("ALTER TABLE radius_wspotv3.campaign_media_image 
            ADD COLUMN image_desktop2 VARCHAR(100) NULL AFTER image_desktop, 
            ADD COLUMN image_desktop3 VARCHAR(100) NULL AFTER image_desktop2, 
            ADD COLUMN image_mobile2 VARCHAR(100) NULL AFTER image_mobile, 
            ADD COLUMN image_mobile3 VARCHAR(100) NULL AFTER image_mobile2;"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Removendo as colunas adicionadas no método up()
        $this->addSql("ALTER TABLE radius_wspotv3.campaign_media_image 
            DROP COLUMN image_desktop2, 
            DROP COLUMN image_desktop3, 
            DROP COLUMN image_mobile2, 
            DROP COLUMN image_mobile3;"
        );
    }
}
