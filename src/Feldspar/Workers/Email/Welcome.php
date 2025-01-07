<?php

declare(strict_types=1);

namespace Feldspar\Workers\Email;

use Feldspar\Entities\Account;
use Feldspar\Workers\Email\EmailWorker;

class Welcome extends EmailWorker
{
    public function __invoke(Account $account): void
    {
        $subject = 'Bonvenon al volontulo.net';
        $contentTemplate = 'welcome';
        $data = [];

        $this->send($account, $subject, $contentTemplate, $data);
    }
}
