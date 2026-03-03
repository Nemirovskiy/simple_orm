<?php

declare(strict_types=1);

namespace SimpleOrm\Db;

use InvalidArgumentException;
use PDOStatement;

/**
 * Упрощённый билдёр запросов в стиле Bitrix D7.
 */
class QueryBuilder
{
    private Connection $connection;

    private string $table = '';

    /** @var string[] */
    private array $select = ['*'];

    /** @var array<int, string> */
    private array $where = [];

    /** @var array<string, mixed> */
    private array $params = [];

    /** @var array<int, string> */
    private array $orderBy = [];

    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function from(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param string[] $fields
     */
    public function select(array $fields): self
    {
        if ($fields === []) {
            $this->select = ['*'];
        } else {
            $this->select = $fields;
        }

        return $this;
    }

    /**
     * Установить фильтр в стиле Bitrix D7.
     *
     * Примеры:
     * ['ID' => 1]
     * ['>=PRICE' => 100]
     * ['%NAME' => 'Ivan']
     * ['@ID' => [1,2,3]]
     * ['!@ID' => [1,2,3]]
     *
     * @param array<string, mixed> $filter
     */
    public function filter(array $filter): self
    {
        foreach ($filter as $key => $value) {
            $this->applyFilterCondition($key, $value);
        }

        return $this;
    }

    /**
     * @param array<string, 'ASC'|'DESC'|string> $order
     */
    public function order(array $order): self
    {
        $this->orderBy = [];

        foreach ($order as $field => $direction) {
            $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $this->orderBy[] = $this->escapeIdentifier($field) . ' ' . $dir;
        }

        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        if ($limit < 0 || $offset < 0) {
            throw new InvalidArgumentException('Limit and offset must be non-negative.');
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function getSql(): string
    {
        if ($this->table === '') {
            throw new InvalidArgumentException('Table name is not set. Use from() before executing the query.');
        }

        $sql = 'SELECT ' . implode(', ', $this->select)
            . ' FROM ' . $this->escapeIdentifier($this->table);

        if ($this->where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->orderBy !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null && $this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return $sql;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function fetch(): ?array
    {
        $stmt = $this->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(): array
    {
        $stmt = $this->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }

    private function execute(): PDOStatement
    {
        $sql = $this->getSql();

        return $this->connection->query($sql, $this->params);
    }

    /**
     * @param mixed $value
     */
    private function applyFilterCondition(string $rawKey, $value): void
    {
        $operator = '=';
        $field = $rawKey;

        // Операторы по аналогии с Bitrix D7: >=FIELD, <=FIELD, >FIELD, <FIELD, !FIELD, %FIELD, @FIELD, !@FIELD
        if (preg_match('/^(>=|<=|>|<|!@|@|!|%)(.+)$/', $rawKey, $matches) === 1) {
            $opToken = $matches[1];
            $field = $matches[2];

            switch ($opToken) {
                case '>':
                case '<':
                case '>=':
                case '<=':
                    $operator = $opToken;
                    break;
                case '!':
                    $operator = '!=';
                    break;
                case '%':
                    $operator = 'LIKE';
                    break;
                case '@':
                    $operator = 'IN';
                    break;
                case '!@':
                    $operator = 'NOT IN';
                    break;
                default:
                    $operator = '=';
            }
        }

        $paramBaseName = $this->normalizeParamName($field);

        if ($operator === 'IN' || $operator === 'NOT IN') {
            if (!is_array($value) || $value === []) {
                throw new InvalidArgumentException('Value for IN/NOT IN filter must be a non-empty array.');
            }

            $placeholders = [];
            foreach (array_values($value) as $index => $val) {
                $paramName = sprintf(':%s_%d', $paramBaseName, $index);
                $placeholders[] = $paramName;
                $this->params[ltrim($paramName, ':')] = $val;
            }

            $this->where[] = sprintf(
                '%s %s (%s)',
                $this->escapeIdentifier($field),
                $operator,
                implode(', ', $placeholders)
            );

            return;
        }

        $paramName = ':' . $paramBaseName;

        if ($operator === 'LIKE') {
            $this->params[ltrim($paramName, ':')] = '%' . $value . '%';
        } else {
            $this->params[ltrim($paramName, ':')] = $value;
        }

        $this->where[] = sprintf(
            '%s %s %s',
            $this->escapeIdentifier($field),
            $operator,
            $paramName
        );
    }

    private function escapeIdentifier(string $name): string
    {
        // Очень простое экранирование идентификаторов под MySQL.
        // Предполагается, что имя таблицы/поля корректно и приходит из кода, а не от пользователя.
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private function normalizeParamName(string $field): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $field) . '_' . count($this->params);
    }
}

