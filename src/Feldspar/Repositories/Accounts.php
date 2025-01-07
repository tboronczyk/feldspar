<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Helpers\DbAccess;
use DateTime;
use PDOException;
use RuntimeException;

class Accounts
{
    /**
     * Constructor
     *
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * Create a new user account
     *
     * @param AccountEntity $account
     * @return int
     * @throws PDOException
     */
    public function create(AccountEntity $account): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO accounts
                 (id, first_name, last_name, email, is_active, created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, 1, ?, ?)",
            [
                $account->firstName,
                $account->lastName,
                $account->email,
                $createdAt,
                $createdAt
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();
        return $id;
    }

    /**
     * Retrieve a user account by id
     *
     * @param int $id
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getById(int $id): ?AccountEntity
    {
        /** @var ?AccountEntity $acct */
        $acct = $this->db->queryRow(
            'SELECT
                 id, first_name AS firstName, last_name AS lastName,
                 email, is_active AS isActive
             FROM
                 accounts
             WHERE
                 id = ?',
            [$id],
            AccountEntity::class
        );

        return $acct;
    }

    /**
     * Retrieve a user account by email address
     *
     * @param string $email
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getByEmail(string $email): ?AccountEntity
    {
        /** @var ?AccountEntity $acct */
        $acct = $this->db->queryRow(
            'SELECT
                 id, first_name AS firstName, last_name AS lastName,
                 email, is_active AS isActive
             FROM
                 accounts
             WHERE
                 email = ?',
            [$email],
            AccountEntity::class
        );

        return $acct;
    }

    /**
     * Retrieve a user account by email address and password
     *
     * @param string $email
     * @param string $password
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getByEmailAndPassword(string $email, string $password): ?AccountEntity
    {
        $acct = $this->getByEmail($email);

        if ($acct === null) {
            return null;
        }

        /** @var ?string $hash */
        $hash = $this->db->queryValue(
            'SELECT
                 hash
             FROM 
                 passwords
             WHERE
                 account_id = ?',
            [$acct->id]
        );

        if (is_null($hash) || !password_verify($password, $hash)) {
            return null;
        }

        return $acct;
    }

    /**
     * Update a user account
     *
     * @param int $id
     * @param AccountEntity $account
     * @return void
     * @throws PDOException
     */
    public function update(int $id, AccountEntity $account): void
    {
        $updatedAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE
                 accounts
             SET
                 first_name = ?, last_name = ?, email = ?, updated_at = ?
             WHERE
                 id = ?',
            [
                $account->firstName,
                $account->lastName,
                $account->email,
                $updatedAt,
                $id
            ]
        );
    }

    /**
     * Update a user's password
     *
     * @param int $id
     * @param string $password
     * @throws PDOException|RuntimeException
     */
    public function updatePassword(int $id, string $password): void
    {
        $updatedAt = (new DateTime())->format('Y-m-d H:i:s');
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->db->query(
            'INSERT INTO passwords
                 (account_id, hash, updated_at)
             VALUES
                 (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 hash = ?,
                 updated_at = ?',
            [
                $id,
                $hash,
                $updatedAt,
                $hash,
                $updatedAt
            ]
        );
    }
}
