<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Helpers\DbAccess;
use PDOException;

class OtpTokens
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        protected DbAccess $db
    ) {
    }

    /**
     * @param int $accountId
     * @param string $hash
     * @throws PDOException
     */
    public function create(int $accountId, string $hash): void
    {
        $this->db->query(
            'INSERT INTO otp_tokens
                 (account_id, hash, created_at)
             VALUES
                 (?, ?, ?)',
            [
                $accountId,
                $hash,
                (new \DateTime())->format('Y-m-d H:i:s')
            ]
        );
    }

    public function getByAccountId(int $accountId): ?string
    {
        $hash = $this->db->queryValue(
            'SELECT
                 hash
             FROM
                 otp_tokens
             WHERE
                 account_id = ?
                 AND created_at > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 20 MINUTE)
             ORDER BY
               created_at DESC
             LIMIT 1',
            [$accountId]
        );
        assert(is_null($hash) || is_string($hash));

        return $hash;
    }

    public function deleteByAccountId(int $accountId): void
    {
        $this->db->query(
            'DELETE FROM otp_tokens WHERE account_id = ?',
            [$accountId]
        );
    }
}
