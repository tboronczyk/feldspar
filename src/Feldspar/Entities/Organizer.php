<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class Organizer
{
    public function __construct(
        public int $id = 0,
        public int $accountId = 0,
        public string $name = '',
        public string $email = '',
        public string $description = '',
    ) {
    }
}
