<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Helpers\DbAccess;
use PDOException;
use RuntimeException;

class Users
{
    /**
     * Constructor
     *
     * @param DbAccess $db
     */
    public function __construct(
        protected DbAccess $db
    ) {
    }

    /**
     * Ensure proper types for returned data
     *
     * @param array $row
     * @return array
     */
    protected function cast(array $row): array
    {
        if (empty($row)) {
            return [];
        }

        if (isset($row['id'])) {
            settype($row['id'], 'int');
        }
        if (isset($row['isActive'])) {
            settype($row['isActive'], 'int');
        }

        return $row;
    }

    /**
     * Create a new account record and return it
     *
     * @param array $data
     * @return array
     * @throws PDOException
     */
    public function create(array $data): array
    {
        $this->db->query(
            "INSERT INTO accounts
             (id, name, email, password, is_active)
             VALUES (NULL, ?, ?, '', 1)",
            [
                $data['name'],
                $data['email']
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();

        $this->updatePassword($id, $data['password']);

        return $this->getById($id);
    }

    /**
     * Retrieve an account by id
     *
     * @param int $id
     * @return array
     * @throws PDOException
     */
    public function getById(int $id): array
    {
        $row = $this->db->queryRow(
            'SELECT
             id, name, email, is_active AS isActive
             FROM accounts
             WHERE id = ?',
            [$id]
        );

        return $this->cast($row);
    }

    /**
     * Retrieve an account by email address
     *
     * @param string $email
     * @return array
     * @throws PDOException
     */
    public function getByEmail(string $email): array
    {
        $row = $this->db->queryRow(
            'SELECT
             id, name, email, is_active AS isActive
             FROM accounts
             WHERE email = ?',
            [$email]
        );

        return $this->cast($row);
    }

    /**
     * Retrieve an account by email address and password
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws PDOException
     */
    public function getByEmailAndPassword(string $email, string $password): ?array
    {
        $row = $this->db->queryRow(
            'SELECT
             id, name, email, password, is_active AS isActive
             FROM accounts
             WHERE email = ?',
            [$email]
        );

        if (empty($row) || !password_verify($password, $row['password'])) {
            return [];
        }

        unset($row['password']);
        return $this->cast($row);
    }

    /**
     * Update an account record
     *
     * @param int $id
     * @param array $data
     * @return void
     * @throws PDOException
     */
    public function update(int $id, array $data): void
    {
        $this->db->query(
            'UPDATE accounts
             SET name = ?, email = ? WHERE id = ?',
            [
                $data['name'],
                $data['email'],
                $id
            ]
        );
    }

    /**
     * Update an account's password
     *
     * @param int $id
     * @param string $password
     * @throws PDOException|RuntimeException
     */
    public function updatePassword(int $id, string $password): void
    {
        $password = password_hash($password, PASSWORD_BCRYPT);
        if (empty($password)) {
            throw new RuntimeException('Password hash failure');
        }

        $this->db->query(
            'UPDATE accounts
             SET password = ?
             WHERE id = ?',
            [
                $password,
                $id
            ]
        );
    }
}
