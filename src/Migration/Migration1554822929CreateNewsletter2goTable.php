<?php declare(strict_types=1);

namespace Newsletter2go\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1554822929CreateNewsletter2goTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1554822929;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `newsletter2go_config` (
    `id` BINARY(16) NOT NULL,
	`name` varchar(255) NOT NULL UNIQUE,
	`value` LONGTEXT COLLATE utf8mb4_unicode_ci NULL,
	`created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
	PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeQuery("DROP TABLE IF EXISTS `newsletter2go_config`;");
    }
}
