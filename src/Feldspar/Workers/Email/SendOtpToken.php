<?php

declare(strict_types=1);

namespace Feldspar\Workers\Email;

use Feldspar\Entities\Account;
use Feldspar\Workers\Email\EmailWorker;

class SendOtpToken extends EmailWorker
{
    public function __invoke(Account $account, string $token): void
    {
        $subject = 'One-Time Password Reset Token';
        $body =
            "A one-time use password reset token was requested for\n" .
            "your account. You can complete the reset process with\n" .
            "the following token:\n" .
            "\n" .
            "    $token \n" .
            "\n" .
            "This token is only valid for 20 minutes.\n" .
            "\n" .
            "If you did not make this request, you may ignore this\n" .
            "email.\n";

        $this->send($account, $subject, $body);
    }
}
