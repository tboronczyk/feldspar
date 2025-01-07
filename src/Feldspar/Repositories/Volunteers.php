<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\Volunteer as VolunteerEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class Volunteers
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param VolunteerEntity $volunteer
     * @return int
     * @throws PDOException
     */
    public function create(VolunteerEntity $volunteer): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO volunteers
                 (id, account_id, name, description,
                 created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, ?, ?)",
            [
                $volunteer->accountId,
                $volunteer->name,
                $volunteer->description,
                $createdAt,
                $createdAt,
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();
        return $id;
    }

    /**
     * @param int $id
     * @return ?VolunteerEntity
     * @throws PDOException
     */
    public function getById(int $id): ?VolunteerEntity
    {
        $volunteer = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId,
                 name, description
             FROM
                 volunteers
             WHERE
                 id = ?',
            [$id],
            VolunteerEntity::class
        );
        assert(is_null($volunteer) || $volunteer instanceof VolunteerEntity);

        return $volunteer;
    }

        /**
     * @param int $id
     * @return ?VolunteerEntity
     * @throws PDOException
     */
    public function getByAccountId(int $id): ?VolunteerEntity
    {
        $volunteer = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId,
                 name, description
             FROM
                 volunteers
             WHERE
                 account_id = ?',
            [$id],
            VolunteerEntity::class
        );
        assert(is_null($volunteer) || $volunteer instanceof VolunteerEntity);

        return $volunteer;
    }

    /**
     * @param int $id
     * @param VolunteerEntity $volunteer
     * @return void
     * @throws PDOException
     */
    public function update(int $id, VolunteerEntity $volunteer): void
    {
        $this->db->query(
            'UPDATE
                 volunteers
             SET
                 name = ?,
                 description = ?,
                 updated_at = ?
             WHERE
                 id = ?',
            [
                $volunteer->name,
                $volunteer->description,
                (new DateTime())->format('Y-m-d H:i:s'),
                $id,
            ]
        );
    }
}
