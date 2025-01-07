<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Traits\RedirectResponse;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;

class Controller
{
    use RedirectResponse;

    /**
     * @param AccountEntity $account
     * @param Session $session
     * @param SessionManager $sessionManager
     * @return void
     */
    protected function setAuthState(AccountEntity $account, Session $session, SessionManager $sessionManager): void
    {
        $sessionManager->regenerateId();
        $session->set('isAuthenticated', true);
        $session->set('account', [
            'id' => $account->id,
            'username' => $account->username,
        ]);
    }

    /**
     * @param Session $session
     * @param SessionManager $sessionManager
     * @return void
     */
    protected function clearAuthState(Session $session, SessionManager $sessionManager): void
    {
        $session->set('isAuthenticated', false);
        $session->delete('account');
        $sessionManager->regenerateId();
    }
}
