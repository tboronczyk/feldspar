<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class VolunteerProfile
{
    public function __construct(
        public int $accountId = 0,
        public string $name = '',
        public string $website = '',
        public string $email = '',
        public string $description = '',
    ) {
    }
}
