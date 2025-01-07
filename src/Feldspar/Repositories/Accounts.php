<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class Accounts
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param AccountEntity $account
     * @return int
     * @throws PDOException
     */
    public function create(AccountEntity $account): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO accounts
                 (id, username, email, is_active,
                 created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, ?, ?)",
            [
                $account->username,
                $account->email,
                $account->isActive,
                $createdAt,
                $createdAt,
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();
        return $id;
    }

    /**
     * @param int $id
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getById(int $id): ?AccountEntity
    {
        $account = $this->db->queryRow(
            'SELECT
                 id, username, email, is_active AS isActive
             FROM
                 accounts
             WHERE
                 id = ?',
            [$id],
            AccountEntity::class
        );
        assert(is_null($account) || $account instanceof AccountEntity);

        return $account;
    }

    /**
     * @param string $username
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getByUsername(string $username): ?AccountEntity
    {
        $account = $this->db->queryRow(
            'SELECT
                 id, username, email, is_active AS isActive
             FROM
                 accounts
             WHERE
                 username = ?',
            [$username],
            AccountEntity::class
        );
        assert(is_null($account) || $account instanceof AccountEntity);

        return $account;
    }

    /**
     * @param string $email
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getByEmail(string $email): ?AccountEntity
    {
        $account = $this->db->queryRow(
            'SELECT
                 id, username, email, is_active AS isActive
             FROM
                 accounts
             WHERE
                 email = ?',
            [$email],
            AccountEntity::class
        );
        assert(is_null($account) || $account instanceof AccountEntity);

        return $account;
    }

    /**
     * @param int $id
     * @param AccountEntity $account
     * @return void
     * @throws PDOException
     */
    public function update(int $id, AccountEntity $account): void
    {
        $this->db->query(
            'UPDATE
                 accounts
             SET
                 username = ?, email = ?, updated_at = ?
             WHERE
                 id = ?',
            [
                $account->username,
                $account->email,
                (new DateTime())->format('Y-m-d H:i:s'),
                $id,
            ]
        );
    }
}
