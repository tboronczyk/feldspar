<?php

declare(strict_types=1);

namespace Feldspar\Services;

use DateTime;
use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Entities\OAuth2Account as OAuth2AccountEntity;
use Feldspar\Entities\VerifyResult;
use Feldspar\Helpers\Db as DbHelper;
use GdImage;
use PDOException;

class Account
{
    /**
     * @param DbHelper $db
     */
    public function __construct(
        private DbHelper $db
    ) {
    }

    private function createDefaultAvatar(): GdImage
    {
        $colorSchemes = [
        [0xb71c1c, 0xe23e3e, 0xec8080, 0xf6c3c3],
        [0xff9800, 0xffb84d, 0xffd699, 0xfff5e5],
        [0x33691e, 0x50a52f, 0x75ce52, 0xa5df8e],
        [0x2734bd, 0x5560dc, 0x949be9, 0xd3d6f6],
        [0x6a1b9a, 0x9528d9, 0xb569e5, 0xd5aaf0],
        ];

        $imgSize = 256;
        $sqSize = 32;
        $numSquares = ceil($imgSize / $sqSize);

        $colorScheme = $colorSchemes[rand(0, count($colorSchemes) - 1)];
        $numColors = count($colorScheme) - 1;

        $img = imagecreatetruecolor($imgSize, $imgSize);

        for ($row = 0; $row < $numSquares; $row++) {
            for ($col = 0; $col < $numSquares; $col++) {
                $x = $row * $sqSize;
                $y = $col * $sqSize;
                imagefilledrectangle($img, $x, $y, $x + $sqSize, $y + $sqSize, $colorScheme[rand(0, $numColors)]);
            }
        }

        return $img;
    }

    /**
     * @param AccountEntity $account
     * @param string $password
     * @return int
     * @throws PDOException
     */
    public function create(AccountEntity $account, string $password): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $this->db->beginTransaction();

            $this->db->query(
                'INSERT INTO accounts
                    (id, first_name, last_name, username, email, country, is_active,
                    created_at, updated_at)
                 VALUES
                    (NULL, ?, ?, ?, ?, ?, ?, ?, ?)',
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
                'INSERT INTO account_profiles
                    (account_id, profile)
                 VALUES
                    (?, ?)',
                [
                    $id,
                    $account->profile,
                ]
            );

            $this->db->query(
                'INSERT INTO passwords
                     (account_id, hash, updated_at)
                 VALUES
                     (?, ?, ?)',
                [
                    $id,
                    $hash,
                    $createdAt,
                ]
            );

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }

        $avatar = $this->createDefaultAvatar();
        $path = getcwd() . '/avatars/' . $id . '.png';
        imagepng($avatar, $path);

        return $id;
    }

    /**
     * @param OAuth2AccountEntity $account
     * @param ?string $avatarUrl
     * @return int
     * @throws PDOException
     */
    public function createOAuth2(OAuth2AccountEntity $account, ?string $avatarUrl): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        try {
            $this->db->beginTransaction();

            $this->db->query(
                'INSERT INTO accounts
                    (id, first_name, last_name, username, email, country, is_active,
                    created_at, updated_at)
                 VALUES
                    (NULL, ?, ?, ?, ?, ?, ?, ?, ?)',
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
                'INSERT INTO account_profiles
                    (account_id, profile)
                 VALUES
                    (?, ?)',
                [
                    $id,
                    $account->profile,
                ]
            );


            $this->db->query(
                'INSERT INTO oauth2_accounts
                     (provider, provider_id, account_id, created_at)
                 VALUES
                     (?, ?, ?, ?)',
                [
                    $account->provider,
                    $account->providerId,
                    $id,
                    $createdAt,
                ]
            );

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }

        $avatar = false;
        if ($avatarUrl !== null) {
            $avatar = imagecreatefromjpeg($avatarUrl);
        }
        if ($avatar === false) {
            $avatar = $this->createDefaultAvatar();
        }

        $path = getcwd() . '/avatars/' . $id . '.png';
        imagepng($avatar, $path);

        return $id;
    }

    /**
     * @param int $accountId
     * @return ?AccountEntity
     * @throws PDOException
     */
    public function getById(int $accountId): ?AccountEntity
    {
        $account = $this->db->queryRow(
            'SELECT
                 a.id, a.first_name AS firstName, a.last_name AS lastName,
                 a.username, a.email, a.country, a.is_active AS isActive,
                 p.profile
             FROM
                 accounts a
                 JOIN account_profiles p ON a.id = p.account_id
             WHERE
                 a.id = ?',
            [$accountId],
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
                 a.id, a.first_name AS firstName, a.last_name AS lastName,
                 a.username, a.email, a.country, a.is_active AS isActive,
                 p.profile
             FROM
                 accounts a
                 JOIN account_profiles p ON a.id = p.account_id
             WHERE
                 a.email = ?',
            [$email],
            AccountEntity::class
        );
        assert(is_null($account) || $account instanceof AccountEntity);

        return $account;
    }

    /**
     * @param string $provider
     * @param string $providerId
     * @return ?OAuth2AccountEntity
     * @throws PDOException
     */
    public function getByProvider(string $provider, string $providerId): ?OAuth2AccountEntity
    {
        $account = $this->db->queryRow(
            'SELECT
                 a.id, a.first_name AS firstName, a.last_name AS lastName,
                 a.username, a.email, a.country, a.is_active AS isActive,
                 p.profile,
                 o.provider, o.provider_id AS providerId
             FROM
                 accounts a
                 JOIN account_profiles p ON a.id = p.account_id
                 JOIN oauth2_accounts o ON a.id = o.account_id
             WHERE
                 o.provider = ?
                 AND o.provider_id = ?',
            [
                $provider,
                $providerId,
            ],
            OAuth2AccountEntity::class
        );
        assert(is_null($account) || $account instanceof OAuth2AccountEntity);

        return $account;
    }

    /**
     * @param int $accountId
     * @param string $provider
     * @param string $providerId
     * @throws PDOException
     */
    public function linkOAuth2(int $accountId, string $provider, string $providerId): void
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            'INSERT INTO oauth2_accounts
                 (provider, provider_id, account_id, created_at, updated_at)
             VALUES
                 (?, ?, ?, ?, ?)',
            [
                $provider,
                $providerId,
                $accountId,
                $createdAt,
                $createdAt,
            ]
        );
    }

    /**
     * @param int $accountId
     * @param AccountEntity $account
     * @return void
     * @throws PDOException
     */
    public function update(int $accountId, AccountEntity $account): void
    {
        $updatedAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE
                 accounts a
                 JOIN account_profiles p ON a.id = p.account_id
             SET
                 a.first_name = ?, a.last_name = ?, a.username = ?, a.email = ?,
                 a.country = ?, a.updated_at = ?, p.profile = ?, p.updated_at = ?
             WHERE
                 a.id = ?',
            [
                $account->firstName,
                $account->lastName,
                $account->username,
                $account->email,
                $account->country,
                $updatedAt,
                $account->profile,
                $updatedAt,
                $accountId,
            ]
        );
    }

    /**
     * @param int $accountId
     * @param string $password
     * @throws PDOException
     */
    public function updatePassword(int $accountId, string $password): void
    {
        $updatedAt = (new DateTime())->format('Y-m-d H:i:s');
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->db->query(
            'UPDATE
                 passwords
             SET
                 hash = ?,
                 updated_at = ?
             WHERE
                 account_id = ?',
            [
                $hash,
                $updatedAt,
                $accountId,
            ]
        );
    }

    /**
     * @param string $username
     * @return VerifyResult
     * @throws PDOException
     */
    public function verifyUsername(string $username): VerifyResult
    {
        $id = $this->db->queryValue(
            'SELECT id FROM accounts WHERE username = ?',
            [$username]
        );

        return new VerifyResult(
            result: !is_null($id),
            accountId: (int)$id,
        );
    }

    /**
     * @param string $email
     * @return VerifyResult
     * @throws PDOException
     */
    public function verifyEmail(string $email): VerifyResult
    {
        $id = $this->db->queryValue(
            'SELECT id FROM accounts WHERE email = ?',
            [$email]
        );

        return new VerifyResult(
            result: !is_null($id),
            accountId: (int)$id,
        );
    }

    /**
     * @param int $accountId
     * @param string $password
     * @return VerifyResult
     * @throws PDOException
     */
    public function verifyPassword(int $accountId, string $password): VerifyResult
    {
        $hash = $this->db->queryValue(
            'SELECT hash FROM passwords WHERE account_id = ?',
            [$accountId]
        );
        assert(is_null($hash) || is_string($hash));

        return new VerifyResult(
            result: password_verify($password, (string)$hash),
            accountId: $accountId,
        );
    }

    /**
     * @param int $accountId
     * @return list<string>
     * @throws PDOException
     */
    public function getAuthTypes(int $accountId): array
    {
        $authTypes = $this->db->queryRows(
            "SELECT provider FROM oauth2_accounts WHERE account_id = ?
             UNION
             SELECT 'password' AS provider FROM passwords WHERE account_id = ?",
            [
                $accountId,
                $accountId,
            ]
        );

        return array_column($authTypes, 'provider');
    }
}
