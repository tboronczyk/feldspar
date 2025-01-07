<?php

declare(strict_types=1);

namespace Feldspar\Workers\Email;

use Feldspar\Entities\Account;
use Feldspar\Workers\Email\EmailWorker;

class ForgotPassword extends EmailWorker
{
    public function __invoke(Account $account, string $token): void
    {
        $subject = 'Restarigi pasvorton';
        $contentTemplate = 'forgot-password';
        $data = [
            'token' => $token
        ];

        $this->send($account, $subject, $contentTemplate, $data);
    }
}
