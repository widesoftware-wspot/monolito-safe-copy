<?php

namespace Wspot\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Esta Migration foi criada para adequar a tabela  campaign_media_video para receber media MP4 também
 * hoje ela tem apenas campo para link do vídeo HLS
 */
class Version20211007144015 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
       $this->addSql("ALTER TABLE campaign_media_video ADD COLUMN url_mp4 VARCHAR(255) AFTER step");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
		$this->addSql("ALTER TABLE campaign_media_video DROP COLUMN url_mp4");
    }
}
