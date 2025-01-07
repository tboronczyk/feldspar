<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class OAuth2Account extends Account
{
    public function __construct(
        public int $id = 0,
        public string $firstName = '',
        public string $lastName = '',
        public string $username = '',
        public string $email = '',
        public string $country = '',
        public int $isActive = 0,
        public string $profile = '',
        public string $provider = '',
        public string $providerId = '',
    ) {
        parent::__construct(
            id: $id,
            firstName: $firstName,
            lastName: $lastName,
            username: $username,
            email: $email,
            country: $country,
            isActive: $isActive,
            profile: $profile,
        );
    }
}
