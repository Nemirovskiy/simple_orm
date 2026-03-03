<?php

declare(strict_types=1);

namespace App\Repository;

use App\Db\DbConnection;

class UserRepository extends BaseRepository
{
    protected string $tableName = 'users';

    protected string $primaryKey = 'ID';

    public function __construct(DbConnection $connection)
    {
        parent::__construct($connection);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->createQuery()
            ->filter([
                'EMAIL' => $email,
            ])
            ->limit(1)
            ->fetch();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findActive(): array
    {
        return $this->createQuery()
            ->filter([
                'ACTIVE' => 'Y',
            ])
            ->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByRole(string $role): array
    {
        return $this->createQuery()
            ->filter([
                'ROLE' => $role,
            ])
            ->fetchAll();
    }

    /**
     * SQL-структура таблицы `users`.
     */
    protected function getCreateTableSql(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS `users` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `NAME` VARCHAR(255) NOT NULL,
  `EMAIL` VARCHAR(255) NOT NULL,
  `ACTIVE` CHAR(1) NOT NULL DEFAULT 'Y',
  `AGE` INT UNSIGNED NULL,
  `ROLE` VARCHAR(50) NULL,
  `CREATED_AT` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPDATED_AT` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UX_users_email` (`EMAIL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }
}

