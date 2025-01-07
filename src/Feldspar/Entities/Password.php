<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class Password
{
    public function __construct(
        public int $accountId = 0,
        public string $hash = '',
    ) {}
}
