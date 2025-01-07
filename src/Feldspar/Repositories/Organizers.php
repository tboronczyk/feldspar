<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use DateTime;
use Feldspar\Entities\Organizer as OrganizerEntity;
use Feldspar\Helpers\DbAccess;
use PDOException;

class Organizers
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        private DbAccess $db
    ) {
    }

    /**
     * @param OrganizerEntity $organizer
     * @return int
     * @throws PDOException
     */
    public function create(OrganizerEntity $organizer): int
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO organizers
                 (id, account_id, name, email, description,
                 created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, ?, ?, ?)",
            [
                $organizer->accountId,
                $organizer->name,
                $organizer->email,
                $organizer->description,
                $createdAt,
                $createdAt,
            ]
        );

        $id = (int)$this->db->getPdo()->lastInsertId();
        return $id;
    }

    /**
     * @param int $id
     * @return ?OrganizerEntity
     * @throws PDOException
     */
    public function getById(int $id): ?OrganizerEntity
    {
        $organizer = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId,
                 name, email, description
             FROM
                 organizers
             WHERE
                 id = ?',
            [$id],
            OrganizerEntity::class
        );
        assert(is_null($organizer) || $organizer instanceof OrganizerEntity);

        return $organizer;
    }

    /**
     * @param int $id
     * @return ?OrganizerEntity
     * @throws PDOException
     */
    public function getByAccountId(int $id): ?OrganizerEntity
    {
        $organizer = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId,
                 name, email, description
             FROM
                 organizers
             WHERE
                 account_id = ?',
            [$id],
            OrganizerEntity::class
        );
        assert(is_null($organizer) || $organizer instanceof OrganizerEntity);

        return $organizer;
    }

    /**
     * @param string $name
     * @return ?OrganizerEntity
     * @throws PDOException
     */
    public function getByName(string $name): ?OrganizerEntity
    {
        $organizer = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId,
                 name, email, description
             FROM
                 organizers
             WHERE
                 name = ?',
            [$name],
            OrganizerEntity::class
        );
        assert(is_null($organizer) || $organizer instanceof OrganizerEntity);

        return $organizer;
    }

    /**
     * @param string $email
     * @return ?OrganizerEntity
     * @throws PDOException
     */
    public function getByEmail(string $email): ?OrganizerEntity
    {
        $organizer = $this->db->queryRow(
            'SELECT
                 id, account_id AS accountId,
                 name, email, description
             FROM
                 organizers
             WHERE
                 id = ?',
            [$email],
            OrganizerEntity::class
        );
        assert(is_null($organizer) || $organizer instanceof OrganizerEntity);

        return $organizer;
    }

    /**
     * @param int $id
     * @param OrganizerEntity $organizer
     * @return void
     * @throws PDOException
     */
    public function update(int $id, OrganizerEntity $organizer): void
    {
        $this->db->query(
            'UPDATE
                 organizers
             SET
                 name = ?,
                 email = ?,
                 description = ?,
                 updated_at = ?
             WHERE
                 id = ?',
            [
                $organizer->name,
                $organizer->email,
                $organizer->description,
                (new DateTime())->format('Y-m-d H:i:s'),
                $id,
            ]
        );
    }
}
