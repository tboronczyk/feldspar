<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class VerifyResult
{
    public function __construct(
        public bool $result = false,
        public int $accountId = 0,
    ) {
    }
}
