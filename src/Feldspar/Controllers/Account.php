<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Passwords as PasswordsRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class Account extends Controller
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
     * @param array<string,string> $args
     * @return Response
     */
    public function signup(Request $req, Response $resp, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $params = [
            'username' => trim($body['username'] ?? ''),
            'email' => trim($body['email'] ?? ''),
            'password' => trim($body['password'] ?? ''),
            'confirmPassword' => trim($body['confirmPassword'] ?? ''),
        ];

        $errors = [];

        if ($params['username'] === '') {
            // field is required
            $errors['username'] = 'Kampo deviga';
        } else {
            $verify = $this->accountsRepository->getByUsername($params['username']);
            if (!is_null($verify)) {
                // username already in use
                $errors['username'] = 'Uzantnomo jam uzita';
            }
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        } else {
            $verify = $this->accountsRepository->getByEmail($params['email']);
            if (!is_null($verify)) {
                // email address already in use
                $errors['email'] = 'Retpoŝtadreso jam uzita';
            }
        }

        if ($params['password'] === '') {
            // field is required
            $errors['password'] = 'Kampo deviga';
        }

        if ($params['confirmPassword'] === '') {
            // field is required
            $errors['confirmPassword'] = 'Kampo deviga';
        } elseif ($params['password'] !== $params['confirmPassword']) {
            // confirm password does not match password
            $errors['confirmPassword'] = 'Ripetita pasvorto ne kongruas kun pasvorto';
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('registrigxi-retposxto');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                ['errors' => $errors, ...$params],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $account = new AccountEntity(
            username: $params['username'],
            email: $params['email'],
            isActive: 1,
        );
        $account->id = $this->accountsRepository->create($account);

        $hash = password_hash($params['password'], PASSWORD_BCRYPT);
        $this->passwordsRepository->update($account->id, $hash);

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function getConfirmAccount(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $page = $this->contentRepository->fetch('konto-konfirmi');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString($page->content, ['account' => $account]);

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function postConfirmAccount(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $body = (array)$req->getParsedBody();

        $params = [
            'username' => trim($body['username'] ?? ''),
            'email' => trim($body['email'] ?? ''),
        ];

        $errors = [];

        if ($params['username'] === '') {
            // field is required
            $errors['username'] = 'Kampo deviga';
        } else {
            $verify = $this->accountsRepository->getByUsername($params['username']);
            if (!is_null($verify) && $verify->id !== $account->id) {
                // username already in use
                $errors['username'] = 'Uzantnomo jam uzita';
            }
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        } else {
            $verify = $this->accountsRepository->getByEmail($params['email']);
            if (!is_null($verify) && $verify->id !== $account->id) {
                // email address already in use
                $errors['email'] = 'Retpoŝtadreso jam uzita';
            }
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('konto-konfirmi');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'account' => [...$params]
                ],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $this->accountsRepository->update($account->id, $account);

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function getUpdateAccount(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $page = $this->contentRepository->fetch('konto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString($page->content, ['account' => $account]);

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function postUpdateAccount(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $body = (array)$req->getParsedBody();

        $params = [
            'username' => trim($body['username'] ?? ''),
            'email' => trim($body['email'] ?? ''),
        ];

        $errors = [];

        if ($params['username'] === '') {
            // field is required
            $errors['username'] = 'Kampo deviga';
        } else {
            $verify = $this->accountsRepository->getByUsername($params['username']);
            if (!is_null($verify) && $verify->id !== $account->id) {
                // username already in use
                $errors['username'] = 'Uzantnomo jam uzita';
            }
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        } else {
            $verify = $this->accountsRepository->getByEmail($params['email']);
            if (!is_null($verify) && $verify->id !== $account->id) {
                // email address already in use
                $errors['email'] = 'Retpoŝtadreso jam uzita';
            }
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('konto');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'account' => [...$params]
                ],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $account->username = $params['username'];
        $account->email = $params['email'];

        $this->accountsRepository->update($account->id, $account);

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/');
    }
}
