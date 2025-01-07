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

class User extends Controller
{
    use AuthenticationState;

    protected ContentRepository $content;
    protected PhpSession $session;
    protected UsersRepository $users;

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
     * Set session
     *
     * @param PhpSession $session
     */
    public function setSession(PhpSession $session): void
    {
        $this->session = $session;
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
     * Process signup form
     *
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function postSignup(Request $req, Response $resp, array $args): Response
    {
        $params = (array)$req->getParsedBody();

        try {
            if (
                empty($params['name'])
                || empty($params['email'])
                || empty($params['password'])
            ) {
                throw new ValidationException('All fields are required');
            }

            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }

            $verify = $this->users->getByEmail($params['email']);
            if (!empty($verify) && $verify['email'] == $params['email']) {
                throw new ValidationException('Email already in use');
            }
        } catch (ValidationException $e) {
            $page = $this->content->get('content/signup');
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

        $user = $this->users->create($params);

        $this->setAuthState($this->session, $user);

        return $this->redirectResponse($resp, '/index', 303);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function getUpdateUser(Request $req, Response $resp, array $args): Response
    {
        $user = (array)$this->session->get('user', []);
        if (empty($user)) {
            throw new AuthenticationException('User not authenticated');
        }

        $page = $this->content->get('content/update-user');
        assert(!empty($page));

        $page['content'] = $this->twig->fetchFromString(
            $page['content'],
            $user
        );

        return $this->render($resp, 'layouts/default.html', $page);
    }

    /**
     * Process update form
     *
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function postUpdateUser(Request $req, Response $resp, array $args): Response
    {
        $user = (array)$this->session->get('user', []);
        if (empty($user)) {
            throw new AuthenticationException('User not authenticated');
        }

        $params = (array)$req->getParsedBody();

        try {
            if (
                empty($params['name'])
                || empty($params['email'])
            ) {
                throw new ValidationException('All fields are required');
            }

            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }

            $verify = $this->users->getByEmail($params['email']);
            if (!empty($verify) && $verify['id'] != $user['id']) {
                throw new ValidationException('Email already in use');
            }

            $this->users->update($user['id'], $params);
        } catch (ValidationException $e) {
            $page = $this->content->get('content/update-user');
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

        $user = $this->users->getById($user['id']);
        $this->session->set('user', $user);

        return $this->redirectResponse($resp, '/index', 303);
    }
}
