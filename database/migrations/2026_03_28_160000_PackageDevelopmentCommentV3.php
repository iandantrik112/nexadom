<?php
declare(strict_types=1);

use App\System\Database\NexaMigration;

/**
 * Dokumentasi kolom development: nilai 3 = hidden (tidak di menu / daftar admin).
 */
class PackageDevelopmentCommentV3 extends NexaMigration
{
    public function up(): void
    {
        if (!$this->tableExists('package') || !$this->columnExists('package', 'development')) {
            return;
        }
        $this->execute(
            "ALTER TABLE `package` MODIFY COLUMN `development` INT(11) NOT NULL DEFAULT 2 COMMENT '1=system,2=public,3=hidden'"
        );
    }

    public function down(): void
    {
        if (!$this->tableExists('package') || !$this->columnExists('package', 'development')) {
            return;
        }
        $this->execute(
            "ALTER TABLE `package` MODIFY COLUMN `development` INT(11) NOT NULL DEFAULT 2 COMMENT '1=system, 2=public'"
        );
    }
}
