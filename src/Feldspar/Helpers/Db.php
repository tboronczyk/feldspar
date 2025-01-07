<?php

declare(strict_types=1);

namespace Feldspar\Helpers;

use PDO;
use PDOException;
use PDOStatement;
use UnexpectedValueException;

class Db
{
    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(
        protected PDO $pdo
    ) {
        if ($pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            throw new UnexpectedValueException('PDO::ERRMODE_EXCEPTION expected for PDO::ATTR_ERRMODE');
        }
    }

    /**
     * Return the underlying PDO object
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Begin a transaction
     *
     * @return bool
     * @throws PDOException
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the transaction
     *
     * @return bool
     * @throws PDOException
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the transaction
     *
     * @return bool
     * @throws PDOException
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Prepare and execute a prepared statement
     *
     * @param string $query
     * @param ?array<int|float|string|null> $params (optional)
     * @return PDOStatement
     * @throws PDOException
     */
    protected function stmt(string $query, ?array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Execute a query
     *
     * @param string $query
     * @param ?array<int|float|string|null> $params (optional)
     * @return void
     * @throws PDOException
     */
    public function query(string $query, ?array $params = null): void
    {
        $this->stmt($query, $params);
    }

    /**
     * Execute a query and return the result rows, an empty array if none
     *
     * @param string $query
     * @param ?array<int|float|string|null> $params (optional)
     * @param ?string $classname (optional)
     * @return list<non-empty-array<int|float|string|null>|object>
     * @throws PDOException
     */
    public function queryRows(string $query, ?array $params = null, ?string $classname = null): array
    {
        $stmt = $this->stmt($query, $params);

        $args = ($classname === null)
            ? [PDO::FETCH_ASSOC]
            : [PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $classname];
        $stmt->setFetchMode(...$args);

        try {
            $rows = [];
            // slightly slower but is "type safe"

            while ($row = $stmt->fetch()) {
                /** @var non-empty-array<int|float|string|null>|object $row */
                $rows[] = $row;
            }

            return $rows;
        } finally {
            $stmt->closeCursor();
        }
    }

    /**
     * Execute a query and return a single row, null if none
     *
     * @param string $query
     * @param ?array<int|float|string|null> $params (optional)
     * @param ?string $classname (optional)
     * @return array<int|float|string|null>|object|null
     * @throws PDOException
     */
    public function queryRow(string $query, ?array $params = null, ?string $classname = null): array|object|null
    {
        $stmt = $this->stmt($query, $params);

        $args = ($classname === null)
            ? [PDO::FETCH_ASSOC]
            : [PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $classname];
        $stmt->setFetchMode(...$args);

        try {
            /** @var false|non-empty-array<int|float|string|null>|object $row */
            $row = $stmt->fetch();
            return ($row === false) ? null : $row;
        } finally {
            $stmt->closeCursor();
        }
    }

    /**
     * Execute a query and return the value of the first column of the first
     * row, null if none
     *
     * @param string $query
     * @param ?array<int|float|string|null> $params (optional)
     * @return int|float|string|null
     * @throws PDOException|UnexpectedValueException
     */
    public function queryValue(string $query, ?array $params = null): int|float|string|null
    {
        /** @var array<int|float|string|null>|null $row */
        $row = $this->queryRow($query, $params);

        if (is_null($row) || count($row) === 0) {
            return null;
        }

        $value = reset($row);
        return $value;
    }
}
