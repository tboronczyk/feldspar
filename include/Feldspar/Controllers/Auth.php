<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Exceptions\AuthenticationException;
use Feldspar\Exceptions\ValidationException;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Users as UsersRepository;
use Odan\Session\PhpSession;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Feldspar\Traits\AuthenticationState;

class Auth extends Controller
{
    use AuthenticationState;

    protected ContentRepository $content;
    protected UsersRepository $users;
    protected PhpSession $session;

    /**
     * Set pages repository
     *
     * @param ContentRepository $repo
     */
    public function setContentRepository(ContentRepository $repo): void
    {
        $this->content = $repo;
    }

    /**
     * Set users repository
     *
     * @param UsersRepository $repo
     */
    public function setUsersRepository(UsersRepository $repo): void
    {
        $this->users = $repo;
    }

    /**
     * Set session
     *
     * @param PhpSession $session
     */
    public function setSession(PhpSession $session): void
    {
        $this->session = $session;
    }

    /**
     * Process login form
     *
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function postLogin(Request $req, Response $resp, array $args): Response
    {
        $params = (array)$req->getParsedBody();

        try {
            if (empty($params['email']) || empty($params['password'])) {
                throw new ValidationException('All fields are required');
            }

            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }

            $user = $this->users->getByEmailAndPassword(
                $params['email'],
                $params['password']
            );

            if (empty($user)) {
                throw new AuthenticationException('Invalid email address or password');
            }

            if (!$user['isActive']) {
                throw new AuthenticationException('Account is not active');
            }
        } catch (ValidationException | AuthenticationException $e) {
            $page = $this->content->get('content/login');
            assert(!empty($page));

            $page['content'] = $this->twig->fetchFromString(
                $page['content'],
                [
                    ...(array)$req->getParsedBody(),
                    'error' => $e->getMessage(),
                ]
            );

            return $this->render($resp, 'layouts/default.html', $page);
        }

        $this->setAuthState($this->session, $user);

        return $this->redirectResponse($resp, '/index', 303);
    }

    /**
     * Log out
     *
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function getLogout(Request $req, Response $resp, array $args): Response
    {
        $this->clearAuthState($this->session);

        return $this->redirectResponse($resp, '/login');
    }
}
