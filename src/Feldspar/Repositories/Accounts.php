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
                 (id, first_name, last_name, username, email, country, is_active,
                 created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $account->firstName,
                $account->lastName,
                $account->username,
                $account->email,
                $account->country,
                $account->isActive,
                $createdAt,
                $createdAt,
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();

        $this->db->query(
            "INSERT INTO account_profiles
                 (account_id, profile)
             VALUES
                 (?, '')",
            [$id]
        );

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
                 id, first_name AS firstName, last_name AS lastName,
                 username, email, country, is_active AS isActive
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
                 id, first_name AS firstName, last_name AS lastName,
                 username, email, country, is_active AS isActive
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
                 id, first_name AS firstName, last_name AS lastName,
                 username, email, country, is_active AS isActive
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
                 first_name = ?, last_name = ?, username = ?, email = ?,
                 country = ?, updated_at = ?
             WHERE
                 id = ?',
            [
                $account->firstName,
                $account->lastName,
                $account->username,
                $account->email,
                $account->country,
                (new DateTime())->format('Y-m-d H:i:s'),
                $id,
            ]
        );
    }

    /**
     * @param int $id
     * @return ?string
     * @throws PDOException
     */
    public function getAccountProfile(int $id): ?string
    {
        $profile = $this->db->queryValue(
            'SELECT 
                 profile
             FROM
                 account_profiles
             WHERE
                 account_id = ?',
            [$id]
        );

        assert(is_null($profile) || is_string($profile));

        return $profile;
    }

    /**
     * @param int $id
     * @return ?string
     * @throws PDOException
     */
    public function updateAccountProfile(int $id, string $profile)
    {
        $profile = $this->db->query(
            'UPDATE 
                 account_profiles
             SET
                 profile = ?
             WHERE
                 account_id = ?',
            [
                $profile,
                $id,
            ]
        );
    }
}
