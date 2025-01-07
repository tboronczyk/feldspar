<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class OAuth2Account
{
    public function __construct(
        public string $provider = '',
        public string $providerId = '',
        public int $accountId = 0,
    ) {
    }
}
