<?php

declare(strict_types=1);

namespace SimpleOrm\Db;

use PDO;
use PDOException;
use PDOStatement;

class Connection
{
    private PDO $pdo;

    /**
     * @param string $dbname  Обязательное имя базы данных
     * @param string $username Имя пользователя БД
     * @param string $password Пароль пользователя БД
     * @param array<string, mixed> $options
     *        Дополнительные опции:
     *        - host (string)   — хост MySQL, по умолчанию 127.0.0.1
     *        - port (int)      — порт MySQL, по умолчанию 3306
     *        - charset (string)— кодировка, по умолчанию utf8mb4
     *        + любые PDO::ATTR_* опции
     */
    public function __construct(string $dbname, string $username, string $password, array $options = [])
    {
        $host = $options['host'] ?? '127.0.0.1';
        $port = (int) ($options['port'] ?? 3306);
        $charset = $options['charset'] ?? 'utf8mb4';

        unset($options['host'], $options['port'], $options['charset']);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $dbname,
            $charset
        );

        $defaultOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $options = $options + $defaultOptions;

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Выполнить SELECT-запрос и вернуть PDOStatement.
     *
     * @param string $sql
     * @param array<string, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Выполнить INSERT/UPDATE/DELETE-запрос и вернуть количество затронутых строк.
     *
     * @param string $sql
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}

