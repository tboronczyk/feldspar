<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\Password as PasswordEntity;
use Feldspar\Helpers\Db as DbHelper;
use PDOException;

class Passwords
{
    /**
     * @param DbHelper $db
     */
    public function __construct(
        private DbHelper $db
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
}
