<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Helpers\JWT as JwtHelper;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Passwords as PasswordsRepository;
use Feldspar\Services\Account as AccountService;
use Feldspar\Services\Queue as QueueService;
use Feldspar\Services\Token as TokenService;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig as SlimTwig;

class Password extends Controller
{
    /**
     * @param SlimTwig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param JwtHelper $jwtHelper
     * @param ContentRepository $contentRepository
     * @param PasswordsRepository $passwordsRepository
     * @param AccountService $accountService
     * @param QueueService $queueService
     * @param TokenService $tokenService
     */
    public function __construct(
        protected SlimTwig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected JwtHelper $jwtHelper,
        protected ContentRepository $contentRepository,
        protected PasswordsRepository $passwordsRepository,
        protected AccountService $accountService,
        protected QueueService $queueService,
        protected TokenService $tokenService,
    ) {
    }

    /**
     * @params Request $req
     * @params Response $resp
     * @param array $args
     * returns Response
     */
    public function getUpdatePassword(Request $req, Response $resp, array $args): Response
    {
        $sessAcct = $this->session->get('account');
        $authTypes = $this->accountService->getAuthTypes($sessAcct['id']);
        $enablePasswordChange = in_array('password', $authTypes, true);

        $page = $this->contentRepository->fetch('konto-pasvorto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'password' => [
                    'enablePasswordChange' => $enablePasswordChange
                ]
            ],
        );

        return $this->twig->render(
            $resp,
            'layouts/partial.html',
            [
                'page' => $page,
            ]
        );
    }

    /**
     * @params Request $req
     * @params Response $resp
     * @param array $args
     * returns Response
     */
    public function updatePassword(Request $req, Response $resp, array $args): Response
    {
        $sessAcct = $this->session->get('account');
        $authTypes = $this->accountService->getAuthTypes($sessAcct['id']);
        $enablePasswordChange = in_array('password', $authTypes, true);

        $body = (array)$req->getParsedBody();

        $params = [
            'password' => trim($body['password'] ?? ''),
            'newPassword' => trim($body['newPassword'] ?? ''),
            'confirmPassword' => trim($body['confirmPassword'] ?? ''),
        ];

        $errors = [];

        if ($enablePasswordChange) {
            if ($params['password'] === '') {
                // field is required
                $errors['password'] = 'Kampo deviga';
            } else {
                $verify = $this->accountService->verifyPassword($sessAcct['id'], $params['password']);
                if (!$verify->result) {
                    // incorrect password
                    $errors['password'] = 'Malĝusta pasvorto';
                }
            }

            if ($params['newPassword'] === '') {
                // field is required
                $errors['newPassword'] = 'Kampo deviga';
            }

            if ($params['confirmPassword'] === '') {
                // field is required
                $errors['confirmPassword'] = 'Kampo deviga';
            } elseif ($params['newPassword'] !== $params['confirmPassword']) {
                // confirm password does not match new password
                $errors['confirmPassword'] = 'Ripetita pasvorto ne kongruas kun nova pasvorto';
            }
        }

        if (count($errors) > 0 || !$enablePasswordChange) {
            $page = $this->contentRepository->fetch('konto-pasvorto');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'password' => [
                        ...$params,
                        'enablePasswordChange' => $enablePasswordChange,
                    ],
                ],
            );

            return $this->twig->render(
                $resp,
                'layouts/partial.html',
                [
                    'page' => $page,
                ]
            );
        }

        $this->accountService->updatePassword($sessAcct['id'], $params['newPassword']);

        $this->clearAuthState($this->session, $this->sessionManager);

        // You've changed your password. Please log in again.
        $this->session->getFlash()->add('success', 'Vi ŝanĝis vian pasvorton. Bonvolu re-ensaluti.');

        // see https://v1.htmx.org/docs/#requests
        return $resp->withStatus(204)->withHeader('HX-Redirect', '/ensaluti');
    }

    /**
     * @params Request $req
     * @params Response $resp
     * @param array $args
     * returns Response
     */
    public function forgotPassword(Request $req, Response $resp, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $params = [
            'email' => trim($body['email'] ?? ''),
        ];

        $errors = [];

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        } else {
            $account = $this->accountService->getByEmail($params['email']);
            if (is_null($account)) {
                // avoid further processing and prevent email enumeration
                return $this->redirectResponse($resp, '/pasvorto/sendita');
            }
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('pasvorto-forgesita');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                ['errors' => $errors, ...$params],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $otpToken = $this->tokenService->createOtp($account->id);

        $token = $this->jwtHelper->encode([
            'email' => $account->email,
            'otpToken' => $otpToken,
        ]);

        $this->queueService->queueForgotPasswordEmail($account, $token);

        return $this->redirectResponse($resp, '/pasvorto/sendita');
    }

    /**
     * @params Request $req
     * @params Response $resp
     * @param array $args
     * returns Response
     */
    public function getResetForgotPassword(Request $req, Response $resp, array $args): Response
    {
        $error = false;
        $argToken = trim($args['token'] ?? '');

        if ($argToken === '') {
            $error = true;
        } else {
            try {
                $token = $this->jwtHelper->decode($argToken);
            } catch (\Exception $e) {
            }
            if (!isset($token) || $token['email'] === '' || $token['otpToken'] === '') {
                $error = true;
            }
        }

        if (!$error) {
            $account = $this->accountService->getByEmail($token['email']);
            if (is_null($account)) {
                $error = true;
            } else {
                $verify = $this->tokenService->verifyOtp($account->id, $token['otpToken']);
                if (!$verify->result) {
                    $error = true;
                }
            }
        }

        if ($error) {
            // The link is no longer valid. Please request a new link.
            $this->session->getFlash()->add('error', 'La ligilo ne plu validas. Bonvolu peti novan ligilon.');
            return $this->redirectResponse($resp, '/pasvorto/forgesita');
        }

        $page = $this->contentRepository->fetch('pasvorto-restarigi');

        $page->content = $this->twig->fetchFromString(
            $page->content,
            ['token' => $argToken]
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }

    /**
     * @params Request $req
     * @params Response $resp
     * @param array $args
     * returns Response
     */
    public function resetForgotPassword(Request $req, Response $resp, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $params = [
            'token' => trim($body['token'] ?? ''),
            'password' => trim($body['password'] ?? ''),
            'confirmPassword' => trim($body['confirmPassword'] ?? ''),
        ];

        $errors = [];

        if ($params['password'] === '') {
            // field is required
            $errors['password'] = 'Kampo deviga';
        }

        if ($params['confirmPassword'] === '') {
            // field is required
            $errors['confirmPassword'] = 'Kampo deviga';
        } elseif ($params['password'] !== $params['confirmPassword']) {
            // confirm password does not match new password
            $errors['confirmPassword'] = 'Ripetita pasvorto ne kongruas kun nova pasvorto';
        }

        $invalidToken = false;
        if ($params['token'] === '') {
            $invalidToken = true;
        } else {
            $token = $this->jwtHelper->decode($params['token']);
            if ($token['email'] === '' || $token['otpToken'] === '') {
                $invalidToken = true;
            }
        }

        if (count($errors) === 0 && !$invalidToken) {
            $account = $this->accountService->getByEmail($token['email']);
            if (is_null($account)) {
                $invalidToken = true;
            } else {
                $verify = $this->tokenService->verifyOtp($account->id, $token['otpToken']);
                if (!$verify->result) {
                    $invalidToken = true;
                }
            }
        }

        if ($invalidToken) {
            // The link is no longer valid. Please request a new link.
            $this->session->getFlash()->add('error', 'La ligilo ne plu validas. Bonvolu peti novan ligilon.');
            return $this->redirectResponse($resp, '/pasvorto/forgesita');
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('pasvorto-restarigi');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                ['errors' => $errors, ...$params],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $this->accountService->updatePassword($account->id, $params['password']);

        $this->tokenService->deleteOtpByAccountId($account->id);

        // You've changed your password. Please log in again.
        $this->session->getFlash()->add('success', 'Vi ŝanĝis vian pasvorton. Bonvolu ensaluti.');
        return $this->redirectResponse($resp, '/ensaluti');
    }
}
