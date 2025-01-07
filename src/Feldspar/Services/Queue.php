<?php

declare(strict_types=1);

namespace Feldspar\Services;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Workers\Email\ForgotPassword as ForgotPasswordEmailWorker;
use Redis;

class Queue
{
    /**
     * @param Redis $redis
     */
    public function __construct(
        protected Redis $redis,
    ) {
    }

    /**
     * @param AccountEntity $account
     * @param string $token
     * @return bool
     */
    public function queueForgotPasswordEmail(AccountEntity $account, string $token): bool
    {
        $queueName = 'feldspar.workers';

        $result = $this->redis->lPush(
            $queueName,
            [
                'worker' => ForgotPasswordEmailWorker::class,
                'args' => [
                    'account' => $account,
                    'token' => $token,
                ]
            ]
        );

        return (is_int($result));
    }
}
