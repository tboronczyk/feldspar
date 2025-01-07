<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class Account
{
    public function __construct(
        public int $id = 0,
        public string $username = '',
        public string $email = '',
        public int $isActive = 0,
    ) {
    }
}
