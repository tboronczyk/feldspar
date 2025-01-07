<?php

declare(strict_types=1);

namespace Feldspar\Helpers;

use PDO;
use PDOException;
use PDOStatement;
use UnexpectedValueException;

class DbAccess
{
    protected array $queryLog = [];

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(
        protected PDO $pdo
    ) {
        if ($pdo->getAttribute(PDO::ATTR_ERRMODE) != PDO::ERRMODE_EXCEPTION) {
            throw new UnexpectedValueException('PDO error mode must be set to PDO::ERRMODE_EXCEPTION');
        }
    }

    /**
     * Return the underlying PDO connection object
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Return query log
     *
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Prepare and execute a prepared statement
     *
     * @param string $query
     * @param ?array $params (optional)
     * @return PDOStatement
     * @throws PDOException
     */
    protected function stmt(string $query, ?array $params): PDOStatement
    {
        $this->queryLog[] = [
            'query' => $query,
            'params' => $params,
        ];

        $stmt = $this->pdo->prepare($query);
        assert($stmt instanceof PDOStatement);

        $result = $stmt->execute($params);
        assert($result == true);

        return $stmt;
    }

    /**
     * Execute a query
     *
     * @param string $query
     * @param ?array $params (optional)
     * @return void
     * @throws PDOException
     */
    public function query(string $query, ?array $params = null): void
    {
        $this->stmt($query, $params);
    }

    /**
     * Execute a query and return the result rows, an empty array if no rows
     *
     * @param string $query
     * @param ?array $params (optional)
     * @return array
     * @throws PDOException
     */
    public function queryRows(string $query, ?array $params = null): array
    {
        $stmt = $this->stmt($query, $params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = $stmt->closeCursor();
        assert($result == true);

        return $rows;
    }

    /**
     * Execute a query and return a single row, an empty array if no row
     *
     * @param string $query
     * @param ?array $params (optional)
     * @return array
     * @throws PDOException
     */
    public function queryRow(string $query, ?array $params = null): array
    {
        $stmt = $this->stmt($query, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        assert(is_array($row) || $row === false);

        if ($row === false) {
            $row = [];
        }

        $result = $stmt->closeCursor();
        assert($result == true);

        return $row;
    }

    /**
     * Execute a query and return the value of the first column of the first
     * row, null if no row
     *
     * @param string $query
     * @param ?array $params (optional)
     * @return int|float|string|null
     * @throws PDOException|UnexpectedValueException
     */
    public function queryValue(string $query, ?array $params = null): int|float|string|null
    {
        $row = $this->queryRow($query, $params);
        if (count($row) == 0) {
            return null;
        }

        $value = reset($row);
        return match (gettype($value)) {
            'integer',
            'double', // float
            'string',
            'NULL' => $value,
            default => throw new UnexpectedValueException('Unexpected value type')
        };
    }
}
