<?php

declare(strict_types=1);

namespace Plugin\mgd_slider\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Tabelle für die Slider - Namensmuster Migration<Zeitstempel> nach JTL5-Doku, ungetestet (zu prüfen)
 */
class Migration20261028000000 extends Migration implements IMigration
{
    public function up(): void
    {
        // gleiche SQL wie database/schema.sql
        $this->execute(
            'CREATE TABLE IF NOT EXISTS xplugin_mgd_slider ('
            . 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, '
            . 'name VARCHAR(191) NOT NULL, '
            . 'config LONGTEXT NOT NULL, '
            . 'created_at DATETIME NOT NULL, '
            . 'updated_at DATETIME NOT NULL'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS xplugin_mgd_slider');
    }
}
