<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\Password as PasswordEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class Passwords
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param int $accountId
     * @param string $hash
     * @throws PDOException
     */
    public function update(int $accountId, string $hash): void
    {
        $updatedAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO passwords
                 (account_id, hash, updated_at)
             VALUES
                 (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 hash = ?,
                 updated_at = ?",
            [
                $accountId,
                $hash,
                $updatedAt,
                $hash,
                $updatedAt,
            ]
        );
    }

    /**
     * @param int $accountId
     * @return ?PasswordEntity
     * @throws PDOException
     */
    public function getByAccountId(int $accountId): ?PasswordEntity
    {
        $password = $this->db->queryRow(
            'SELECT
                 account_id AS accountId, hash
             FROM
                 passwords
             WHERE
                 account_id = ?',
            [$accountId],
            PasswordEntity::class
        );
        assert(is_null($password) || $password instanceof PasswordEntity);

        return $password;
    }
}
