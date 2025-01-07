<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class Account
{
    public int $id = 0;
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public int $isActive = 0;

    /**
     * @param array<string, string> $params
     */
    public static function fromRequestParams(array $params): self
    {
        $acct = new self();
        $acct->firstName = $params['firstName'];
        $acct->lastName = $params['lastName'];
        $acct->email = $params['email'];
        return $acct;
    }
}
