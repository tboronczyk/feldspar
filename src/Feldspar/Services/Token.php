<?php

declare(strict_types=1);

namespace Feldspar\Services;

use Feldspar\Entities\VerifyResult;
use Feldspar\Helpers\Db as DbHelper;
use PDOException;

class Token
{
    /**
     * @param DbHelper $db
     */
    public function __construct(
        protected DbHelper $db
    ) {
    }

    /**
     * @param int $accountId
     * @return string
     * @throws PDOException
     */
    public function createOtp(int $accountId): string
    {
        $otpToken = bin2hex(random_bytes(4));
        $hash = password_hash($otpToken, PASSWORD_BCRYPT);

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

        return $otpToken;
    }

  /**
     * @param int $accountId
     * @param string $otpToken
     * @return VerifyResult
     * @throws PDOException
     */
    public function verifyOtp(int $accountId, string $otpToken): VerifyResult
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

        return new VerifyResult(
            result: password_verify($otpToken, (string)$hash),
            accountId: $accountId,
        );
    }

    public function deleteOtpByAccountId(int $accountId): void
    {
        $this->db->query(
            'DELETE FROM otp_tokens WHERE account_id = ?',
            [$accountId]
        );
    }
}
