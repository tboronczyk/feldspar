<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\OAuth2Account as OAuth2AccountEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class OAuth2Accounts
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param OAuth2AccountEntity $account
     * @return void
     * @throws PDOException
     */
    public function create(OAuth2AccountEntity $account): void
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO oauth2_accounts
                 (provider, provider_id, account_id, created_at)
             VALUES
                 (?, ?, ?, ?)",
            [
                $account->provider,
                $account->providerId,
                $account->accountId,
                $createdAt,
            ]
        );
    }

    /**
     * @param string $provider
     * @param string $providerId
     * @return ?OAuth2AccountEntity
     * @throws PDOException
     */
    public function getByProviderId(string $provider, string $providerId): ?OAuth2AccountEntity
    {
        $account = $this->db->queryRow(
            'SELECT
                 provider, provider_id AS providerId, account_id AS accountId
             FROM
                 oauth2_accounts
             WHERE
                 provider = ?
                 AND provider_id = ?',
            [
                $provider,
                $providerId,
            ],
            OAuth2AccountEntity::class
        );
        assert(is_null($account) || $account instanceof OAuth2AccountEntity);

        return $account;
    }
}
