<?php
declare(strict_types=1);

use App\System\Database\NexaMigration;
use App\System\Database\NexaSchema;

/**
 * Tabel package - Daftar akses/modul yang dapat diberikan ke user
 */
class CreatePackageTable extends NexaMigration
{
    public function up(): void
    {
        $this->createTable('package', function (NexaSchema $table) {
            $table->column('id', 'INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
            $table->column('key', 'VARCHAR(50) NOT NULL COMMENT \'Slug untuk URL dan user.package\'');
            $table->column('label', 'VARCHAR(100) NOT NULL');
            $table->column('icon', 'VARCHAR(50) DEFAULT \'fas fa-circle\'');
            $table->column('sort_order', 'INT(11) DEFAULT 0');
            $table->column('url', 'VARCHAR(150) DEFAULT NULL COMMENT \'Path relatif, kosong=gunakan key\'');
            $table->unique('key');
        });
        $this->execute("INSERT INTO `package` (`key`, label, icon, sort_order) VALUES
            ('package', 'Kelola Package', 'fas fa-key', 0),
            ('user', 'User Management', 'fas fa-users', 10),
            ('theme', 'Theme', 'fas fa-palette', 20),
            ('distro', 'Distro', 'fas fa-box-open', 25),
            ('example', 'Example Pages', 'fas fa-code', 30)");
    }

    public function down(): void
    {
        $this->dropTable('package');
    }
}
