<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Helpers\DbAccess;
use PDOException;
use RuntimeException;

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

        if (isset($row['accountId'])) {
            settype($row['accountId'], 'int');
        }

        return $row;
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
        $hashedToken = password_hash($token, PASSWORD_BCRYPT);
        if (empty($hashedToken)) {
            throw new RuntimeException('Token hash failure');
        }

        $this->db->query(
            'INSERT INTO otp_tokens
             (account_id, token, ts_created)
             VALUES
             (?, ?, CURRENT_TIMESTAMP)',
            [
                $accountId,
                $hashedToken
            ]
        );

        return $token;
    }

    /**
     * Retrieve OTP token info
     *
     * @param string $token
     * @return ?array
     * @throws PDOException
     */
    public function getByAccountIdAndToken(int $accountId, string $token): ?array
    {
        $row = $this->db->queryRow(
            'SELECT
                 account_id AS accountId, token, ts_created AS tsCreated
             FROM
                 otp_tokens
             WHERE
                 account_id = ?
                 AND ts_created > DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 2 HOUR)
             ORDER BY
                 ts_created DESC
             LIMIT 1',
            [$accountId]
        );

        if (empty($row) || !password_verify($token, $row['token'])) {
            return [];
        }

        unset($row['token']);
        return $this->cast($row);
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
