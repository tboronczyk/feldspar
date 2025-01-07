<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class Task
{
    public function __construct(
        public int $id = 0,
        public int $organizerId = 0,
        public string $name = '',
        public string $description = '',
    ) {
    }
}
