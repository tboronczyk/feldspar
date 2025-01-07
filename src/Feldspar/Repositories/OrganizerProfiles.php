<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\OrganizerProfile as OrganizerProfileEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class OrganizerProfiles
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param OrganizerProfileEntity $profile
     * @return int
     * @throws PDOException
     */
    public function create(OrganizerProfileEntity $profile): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO organizer_profiles
                 (id, account_id, name, website, email, mission,
                 description, created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $profile->accountId,
                $profile->name,
                $profile->website,
                $profile->email,
                $profile->mission,
                $profile->description,
                $createdAt,
                $createdAt,
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();
        return $id;
    }

    /**
     * @param int $id
     * @return ?OrganizerProfileEntity
     * @throws PDOException
     */
    public function getById(int $id): ?OrganizerProfileEntity
    {
        $profile = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId, name, website,
                 email, mission, description
             FROM
                 organizer_profiles
             WHERE
                 id = ?',
            [$id],
            OrganizerProfileEntity::class
        );
        assert(is_null($profile) || $profile instanceof OrganizerProfileEntity);

        return $profile;
    }

    /**
     * @param int $id
     * @return ?OrganizerProfileEntity
     * @throws PDOException
     */
    public function getByAccountId(int $id): ?OrganizerProfileEntity
    {
        $profile = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId, name, website,
                 email, mission, description
             FROM
                 organizer_profiles
             WHERE
                 account_id = ?',
            [$id],
            OrganizerProfileEntity::class
        );
        assert(is_null($profile) || $profile instanceof OrganizerProfileEntity);

        return $profile;
    }

    /**
     * @param string $name
     * @return ?OrganizerProfileEntity
     * @throws PDOException
     */
    public function getByName(string $name): ?OrganizerProfileEntity
    {
        $profile = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId, name, website,
                 email, mission, description
             FROM
                 organizer_profiles
             WHERE
                 name = ?',
            [$name],
            OrganizerProfileEntity::class
        );
        assert(is_null($profile) || $profile instanceof OrganizerProfileEntity);

        return $profile;
    }

    /**
     * @param string $email
     * @return ?OrganizerProfileEntity
     * @throws PDOException
     */
    public function getByEmail(string $email): ?OrganizerProfileEntity
    {
        $profile = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId, name, website,
                 email, mission, description
             FROM
                 organizer_profiles
             WHERE
                 id = ?',
            [$email],
            OrganizerProfileEntity::class
        );
        assert(is_null($profile) || $profile instanceof OrganizerProfileEntity);

        return $profile;
    }

    /**
     * @param int $id
     * @param OrganizerProfileEntity $profile
     * @return void
     * @throws PDOException
     */
    public function update(int $id, OrganizerProfileEntity $profile): void
    {
        $this->db->query(
            'UPDATE
                 organizer_profiles
             SET
                 name = ?,
                 website = ?,
                 email = ?,
                 mission = ?,
                 description = ?,
                 updated_at = ?
             WHERE
                 id = ?',
            [
                $profile->name,
                $profile->website,
                $profile->email,
                $profile->mission,
                $profile->description,
                (new DateTime())->format('Y-m-d H:i:s'),
                $id,
            ]
        );
    }
}
