<?php

declare(strict_types=1);

namespace Feldspar\Traits;

use Odan\Session\PhpSession;

trait AuthenticationState
{
    protected function setAuthState(PhpSession $session, array $user): void
    {
        $session->set('isAuthenticated', true);
        $session->set('user', $user);
    }

    protected function clearAuthState(PhpSession $session): void
    {
        $session->set('isAuthenticated', false);
        $session->delete('user');
    }
}
