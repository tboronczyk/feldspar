<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Entities\Country as CountryEntity;
use Feldspar\Helpers\Db as DbHelper;
use PDOException;

class Countries
{
    /**
     * @param DbHelper $db
     */
    public function __construct(
        private DbHelper $db
    ) {
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $countries = $this->db->queryRows(
            "SELECT
                 id, name
             FROM
                 countries
             ORDER BY
                 name ASC",
            [],
            CountryEntity::class
        );

        return $countries;
    }

    /**
     * @param string $id
     * @return array
     */
    public function getById(string $id): ?array
    {
        $countries = $this->db->queryRow(
            'SELECT
                 id, name
             FROM
                 countries
             WHERE
                 id = ?',
            [$id],
            CountryEntity::class
        );

        return $countries;
    }
}
