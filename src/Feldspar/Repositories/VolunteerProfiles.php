<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\VolunteerProfile as VolunteerProfileEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class VolunteerProfiles
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param VolunteerProfileEntity $profile
     * @return int
     * @throws PDOException
     */
    public function create(VolunteerProfileEntity $profile): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO volunteer_profiles
                 (account_id, name, website, description,
                 created_at, updated_at)
             VALUES
                 (?, ?, ?, ?, ?, ?)",
            [
                $profile->accountId,
                $profile->name,
                $profile->website,
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
     * @return ?VolunteerProfileEntity
     * @throws PDOException
     */
    public function getByAccountId(int $id): ?VolunteerProfileEntity
    {
        $profile = $this->db->queryRow(
            'SELECT
                 account_id AS accountId, name, website,
                 description
             FROM
                 volunteer_profiles
             WHERE
                 account_id = ?',
            [$id],
            VolunteerProfileEntity::class
        );
        assert(is_null($profile) || $profile instanceof VolunteerProfileEntity);

        return $profile;
    }

    /**
     * @param int $id
     * @param VolunteerProfileEntity $profile
     * @return void
     * @throws PDOException
     */
    public function update(int $id, VolunteerProfileEntity $profile): void
    {
        $this->db->query(
            'UPDATE
                 volunteer_profiles
             SET
                 name = ?,
                 website = ?,
                 description = ?,
                 updated_at = ?
             WHERE
                 account_id = ?',
            [
                $profile->name,
                $profile->website,
                $profile->description,
                (new DateTime())->format('Y-m-d H:i:s'),
                $id,
            ]
        );
    }
}
