<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Passwords as PasswordsRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class Authentication extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param AccountsRepository $accountsRepository
     * @param ContentRepository $contentRepository
     * @param PasswordsRepository $passwordsRepository
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected AccountsRepository $accountsRepository,
        protected ContentRepository $contentRepository,
        protected PasswordsRepository $passwordsRepository,
    ) {
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function login(Request $req, Response $resp, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $params = [
            'email' => trim($body['email'] ?? ''),
            'password' => trim($body['password'] ?? ''),
        ];

        $errors = [];

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        }

        if ($params['password'] === '') {
            // field is required
            $errors['password'] = 'Kampo deviga';
        }

        if (count($errors) === 0) {
            $account = $this->accountsRepository->getByEmail($params['email']);
            if (is_null($account)) {
                // incorrect email address or password
                $errors['error'] = 'Malĝusta retpoŝtadreso aŭ pasvorto';
            } else {
                $password = $this->passwordsRepository->getByAccountId($account->id);
                if (is_null($password) || !password_verify($params['password'], $password->hash)) {
                    // incorrect email address or password
                    $errors['error'] = 'Malĝusta retpoŝtadreso aŭ pasvorto';
                } elseif ($account->isActive !== 1) {
                    // account is inactive
                    $errors['error'] = 'La konto estas neaktiva';
                }
            }
        }

        if (count($errors) !== 0) {
            $page = $this->contentRepository->fetch('ensaluti');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                ['errors' => $errors, ...$params],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function logout(Request $req, Response $resp, array $args): Response
    {
        $this->clearAuthState($this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/ensaluti');
    }
}
