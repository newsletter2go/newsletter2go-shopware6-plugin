<?php declare(strict_types=1);

namespace Swag\Newsletter2go\Migration;

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
CREATE TABLE `newsletter2go_config` (
	id INT NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	value varchar(255) NULL,
	CONSTRAINT s_newsletter2go_config_PK PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
