<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Entities\Task;
use Feldspar\Helpers\DbAccess;
use PDOException;

class Tasks
{
    /**
     * @param DbAccess $db
     */
    public function __construct(
        protected DbAccess $db
    ) {
    }

    /**
     * @param Task $task
     * @throws PDOException
     */
    public function create(Task $task): void
    {
        $createdAt = (new \DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            'INSERT INTO tasks
                 (id, organizer_id, name, description, created_at, updated_at)
             VALUES
                 (NULL, ?, ?, ?, ?, ?)',
            [
                $task->organizerId,
                $task->name,
                $task->description,
                $createdAt,
                $createdAt,
            ]
        );
    }

    /**
     * @param int $id
     * @param Task $task
     * @throws PDOException
     */
    public function update(int $id, Task $task): void
    {
        $updatedAt = (new \DateTime())->format('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE tasks SET
                name = ?,
                description = ?,
                updated_at = ?
            WHERE
                id = ?',
            [
                $task->name,
                $task->description,
                $updatedAt,
                $id,
            ]
        );
    }

    public function getById(int $id): ?Task
    {
        $task = $this->db->queryRow(
            'SELECT
                 id, organizer_id AS organizerId, name, description
             FROM
                 tasks
             WHERE
                 id = ?',
            [$id],
            Task::class
        );
        assert(is_null($task) || $task instanceof Task);

        return $task;
    }

    public function getAllByOrganizerId(int $organizerId): array
    {
        $tasks = $this->db->queryRows(
            'SELECT
                 id, organizer_id AS organizerId, name, description
             FROM
                 tasks
             WHERE
                 organizer_id = ?',
            [$organizerId],
            Task::class
        );

        return $tasks;
    }

    public function getTopByOrganizerId(int $organizerId): array
    {
        $tasks = $this->db->queryRows(
            'SELECT
                 id, organizer_id AS organizerId, name, description
             FROM
                 tasks
             WHERE
                 organizer_id = ?
             ORDER BY
                 created_at DESC
             LIMIT 5',
            [$organizerId],
            Task::class
        );

        return $tasks;
    }
}
