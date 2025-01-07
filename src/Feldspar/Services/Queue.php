<?php

declare(strict_types=1);

namespace Feldspar\Services;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Workers\Email\ForgotPassword as ForgotPasswordEmailWorker;
use Feldspar\Workers\Email\Welcome as WelcomeEmailWorker;
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
        $result = $this->redis->lPush(
            'feldspar.workers',
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

    /**
     * @param AccountEntity $account
     * @return bool
     */
    public function queueWelcomeEmail(AccountEntity $account): bool
    {
        $result = $this->redis->lPush(
            'feldspar.workers',
            [
                'worker' => WelcomeEmailWorker::class,
                'args' => [
                    'account' => $account,
                ]
            ]
        );

        return (is_int($result));
    }
}
