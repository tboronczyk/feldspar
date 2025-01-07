<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Helpers\DbAccess;
use PDOException;

class OtpTokens
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
     * Create a new OTP token record and return the token string
     *
     * @param int $accountId
     * @return string
     * @throws PDOException
     */
    public function create(int $accountId): string
    {
        $token = bin2hex(random_bytes(4));
        $hash = password_hash($token, PASSWORD_BCRYPT);
        $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            'INSERT INTO otp_tokens
                 (account_id, hash, created_at)
             VALUES
                 (?, ?, ?)',
            [
                $accountId,
                $hash,
                $createdAt
            ]
        );

        return $token;
    }

    /**
     * Verify OTP token info
     *
     * @param string $token
     * @return bool
     * @throws PDOException
     */
    public function verifyAccountIdAndToken(int $accountId, string $token): bool
    {
        /** @var ?string $hash */
        $hash = $this->db->queryValue(
            'SELECT
                 hash
             FROM
                 otp_tokens
             WHERE
                 account_id = ?
                 AND created_at > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 15 MINUTE)
             ORDER BY
               created_at DESC
             LIMIT 1',
            [$accountId]
        );

        if (is_null($hash) || !password_verify($token, $hash)) {
            return false;
        }

        return true;
    }

    /**
     * Delete OTP tokens for an account
     *
     * @param int $accountId
     * @return void
     * @throws PDOException
     */
    public function delete(int $accountId): void
    {
        $this->db->query(
            'DELETE FROM otp_tokens WHERE account_id = ?',
            [$accountId]
        );
    }
}
