<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Closure;
use Exception;
use Feldspar\Exceptions\AuthenticationException;
use Feldspar\Exceptions\ValidationException;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OtpTokens as OtpTokenRepository;
use Feldspar\Repositories\Users as UsersRepository;
use Odan\Session\PhpSession;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Feldspar\Workers\Email\SendOtpToken as SendOtpTokenEmailWorker;
use Redis;
use Feldspar\Traits\AuthenticationState;

class Password extends Controller
{
    use AuthenticationState;

    protected Closure $addressFactory;
    protected Redis $redis;
    protected array $config;
    protected ContentRepository $content;
    protected OtpTokenRepository $otpTokens;
    protected PhpSession $session;
    protected UsersRepository $users;

    public function setAddressFactory(Closure $factory): void
    {
        $this->addressFactory = $factory;
    }

    public function setRedis(Redis $redis): void
    {
        $this->redis = $redis;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

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
     * Set OTP token repository
     *
     * @param OtpTokenRepository $repo
     */
    public function setOtpTokenRepository(OtpTokenRepository $repo): void
    {
        $this->otpTokens = $repo;
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

    public function postUpdatePassword(Request $req, Response $resp, array $args): Response
    {
        $user = (array)$this->session->get('user', []);
        if (empty($user)) {
            throw new AuthenticationException('User not authenticated');
        }
        $userId = $user['id'];

        $params = (array)$req->getParsedBody();

        try {
            if (
                empty($params['password'])
                || empty($params['newPassword'])
                || empty($params['confirmPassword'])
            ) {
                throw new ValidationException('All fields are required');
            }

            if ($params['newPassword'] != $params['confirmPassword']) {
                throw new ValidationException('Passwords must match');
            }

            $user = $this->users->getById($userId);
            assert($user != null);

            $verify = $this->users->getByEmailAndPassword($user['email'], $params['password']);
            if (empty($verify)) {
                throw new ValidationException('Invalid current password');
            }

            $this->users->updatePassword($userId, $params['newPassword']);
        } catch (ValidationException $e) {
            $page = $this->content->get('content/update-password');
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

        $this->clearAuthState($this->session);

        return $this->redirectResponse($resp, '/login', 303);
    }

    public function postForgotPassword(Request $req, Response $resp, array $args): Response
    {
        $params = (array)$req->getParsedBody();

        try {
            if (
                empty($params['email'])
            ) {
                throw new ValidationException('All fields are required');
            }

            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }

            $user = $this->users->getByEmail($params['email']);
            if (empty($user)) {
                throw new ValidationException('Invalid email address');
            }

            $token = $this->otpTokens->create($user['id']);

            $queueName = $this->config['redis']['workerQueue'];

            $this->redis->lPush(
                $queueName,
                [
                    'worker' => SendOtpTokenEmailWorker::class,
                    'args' => [
                        'user' => $user,
                        'token' => $token,
                    ]
                ]
            );
        } catch (ValidationException $e) {
            $page = $this->content->get('content/forgot-password');
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

        $page = $this->content->get('content/otp-reset');
        assert(!empty($page));

        $page['content'] = $this->twig->fetchFromString(
            $page['content'],
            ['email' => $user['email']]
        );

        return $this->render($resp, 'layouts/default.html', $page);
    }

    public function postOtpReset(Request $req, Response $resp, array $args): Response
    {
        $params = (array)$req->getParsedBody();

        try {
            if (
                empty($params['email'])
                || empty($params['otpToken'])
                || empty($params['newPassword'])
                || empty($params['confirmPassword'])
            ) {
                throw new ValidationException('All fields are required');
            }

            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }

            if ($params['newPassword'] != $params['confirmPassword']) {
                throw new ValidationException('Passwords must match');
            }

            $user = $this->users->getByEmail($params['email']);
            if (empty($user)) {
                throw new ValidationException('Invalid email address');
            }

            $verify = $this->otpTokens->getByAccountIdAndToken($user['id'], $params['otpToken']);
            if (empty($verify)) {
                throw new ValidationException('Invalid OTP token');
            }

            $this->users->updatePassword($user['id'], $params['newPassword']);
        } catch (ValidationException $e) {
            $page = $this->content->get('content/otp-reset');
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

        return $this->redirectResponse($resp, '/login', 303);
    }
}
