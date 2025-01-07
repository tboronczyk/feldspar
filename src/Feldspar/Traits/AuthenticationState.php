<?php

declare(strict_types=1);

namespace Feldspar\Traits;

use Feldspar\Entities\Account;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;

trait AuthenticationState
{
    protected function setAuthState(Account $account, Session $session, SessionManager $sessionManager): void
    {
        $sessionManager->regenerateId();
        $session->set('isAuthenticated', true);
        $session->set('account', $account);
    }

    protected function clearAuthState(Session $session, SessionManager $sessionManager): void
    {
        $session->set('isAuthenticated', false);
        $session->delete('account');
        $sessionManager->regenerateId();
    }
}
