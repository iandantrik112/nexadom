<?php
declare(strict_types=1);

use App\System\Database\NexaMigration;

/**
 * Sinkronkan tabel package setelah perubahan pada CreatePackageTable:
 * kolom development + baris postingan/development + penyesuaian development/icon.
 * Aman dijalankan jika sebagian sudah ada (idempotent).
 */
class UpdatePackageTableDevelopment extends NexaMigration
{
    public function up(): void
    {
        if (!$this->tableExists('package')) {
            return;
        }

        if (!$this->columnExists('package', 'development')) {
            $this->addColumn('package', 'development', "INT(11) NOT NULL DEFAULT 2 COMMENT '1=system,2=public,3=hidden'");
        }

        $this->execute(
            "INSERT INTO `package` (`key`, label, icon, sort_order, development) VALUES
            ('postingan', 'Postingan', 'fas fa-newspaper', 25, 2),
            ('development', 'Development', 'fas fa-code-branch', 25, 2)
            ON DUPLICATE KEY UPDATE
            `label` = VALUES(`label`),
            `icon` = VALUES(`icon`),
            `sort_order` = VALUES(`sort_order`),
            `development` = VALUES(`development`)"
        );

        $this->execute(
            "UPDATE `package` SET `development` = 1 WHERE `key` IN ('package', 'user', 'theme', 'distro')"
        );
        $this->execute(
            "UPDATE `package` SET `development` = 2, `icon` = 'fas fa-code' WHERE `key` = 'example'"
        );
    }

    public function down(): void
    {
        if (!$this->tableExists('package')) {
            return;
        }
        $this->execute("DELETE FROM `package` WHERE `key` IN ('postingan', 'development')");
        if ($this->columnExists('package', 'development')) {
            $this->dropColumn('package', 'development');
        }
    }
}
