<?php

declare(strict_types=1);

namespace SimpleOrm\Repository;

use SimpleOrm\Db\Connection;
use SimpleOrm\Db\QueryBuilder;

abstract class BaseRepository
{
    protected Connection $connection;

    protected string $tableName;

    protected string $primaryKey = 'ID';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function createQuery(): QueryBuilder
    {
        $query = new QueryBuilder($this->connection);

        return $query->from($this->tableName);
    }

    /**
     * @param mixed $id
     */
    public function findById($id): ?array
    {
        return $this->createQuery()
            ->filter([
                $this->primaryKey => $id,
            ])
            ->limit(1)
            ->fetch();
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, string> $order
     * @return array<int, array<string, mixed>>
     */
    public function findAll(array $filter = [], array $order = [], ?int $limit = null, int $offset = 0): array
    {
        $query = $this->createQuery();

        if ($filter !== []) {
            $query->filter($filter);
        }

        if ($order !== []) {
            $query->order($order);
        }

        if ($limit !== null) {
            $query->limit($limit, $offset);
        }

        return $query->fetchAll();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function add(array $data): string
    {
        $fields = array_keys($data);

        if ($fields === []) {
            return '';
        }

        $placeholders = [];
        $params = [];

        foreach ($fields as $field) {
            $placeholders[] = ':' . $field;
            $params[$field] = $data[$field];
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->escapeIdentifier($this->tableName),
            implode(', ', array_map([$this, 'escapeIdentifier'], $fields)),
            implode(', ', $placeholders)
        );

        $this->connection->execute($sql, $params);

        return $this->connection->getPdo()->lastInsertId();
    }

    /**
     * @param mixed $id
     * @param array<string, mixed> $data
     */
    public function update($id, array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $setParts = [];
        $params = [];

        foreach ($data as $field => $value) {
            $placeholder = ':' . $field;
            $setParts[] = sprintf('%s = %s', $this->escapeIdentifier($field), $placeholder);
            $params[$field] = $value;
        }

        $params['__id'] = $id;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :__id',
            $this->escapeIdentifier($this->tableName),
            implode(', ', $setParts),
            $this->escapeIdentifier($this->primaryKey)
        );

        return $this->connection->execute($sql, $params);
    }

    /**
     * @param mixed $id
     */
    public function delete($id): int
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :id',
            $this->escapeIdentifier($this->tableName),
            $this->escapeIdentifier($this->primaryKey)
        );

        return $this->connection->execute($sql, ['id' => $id]);
    }

    /**
     * Установить (создать, если нет) таблицу в базе данных.
     */
    public function install(): void
    {
        $sql = $this->getCreateTableSql();
        $this->connection->execute($sql);
    }

    /**
     * SQL для создания таблицы.
     */
    abstract protected function getCreateTableSql(): string;

    private function escapeIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}

