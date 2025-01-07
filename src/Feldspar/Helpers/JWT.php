<?php

declare(strict_types=1);

namespace Feldspar\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key as FirebaseKey;

class JWT
{
    /**
     * @param string $secret
     */
    public function __construct(
        protected string $secret,
    ) {
    }

    /**
     * @param array $token
     * @return string
     */
    public function encode(array $token): string
    {
        return FirebaseJWT::encode($token, $this->secret, 'HS256');
    }

    /**
     * @param string $token
     * return array
     */
    public function decode(string $token): array
    {
        return (array)FirebaseJWT::decode($token, new FirebaseKey($this->secret, 'HS256'));
    }
}
