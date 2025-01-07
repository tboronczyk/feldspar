<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Helpers\JWT as JwtHelper;
use Feldspar\Services\Queue as QueueService;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OtpTokens as OtpTokensRepository;
use Feldspar\Repositories\Passwords as PasswordsRepository;
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
     * @param QueueService $queueService
     * @param AccountsRepository $accountsRepository
     * @param ContentRepository $contentRepository
     * @param OtpTokensRepository $otpTokensRepository
     * @param PasswordsRepository $passwordsRepository
     */
    public function __construct(
        protected SlimTwig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected JwtHelper $jwtHelper,
        protected QueueService $queueService,
        protected AccountsRepository $accountsRepository,
        protected ContentRepository $contentRepository,
        protected OtpTokensRepository $otpTokensRepository,
        protected PasswordsRepository $passwordsRepository,
    ) {
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getUpdatePassword(Request $req, Response $resp, array $args): Response
    {
        $page = $this->contentRepository->fetch('konto-pasvorto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString($page->content, []);

        return $this->twig->render(
            $resp,
            'layouts/konto.html',
            [
                'page' => $page,
                'active' => 'pasvorto',
            ]
        );
    }

    /**
     * @params Request $req
     * @params Response $resp
     * @param array $args
     * returns Response
     */
    public function postUpdatePassword(Request $req, Response $resp, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $params = [
            'password' => trim($body['password'] ?? ''),
            'newPassword' => trim($body['newPassword'] ?? ''),
            'confirmPassword' => trim($body['confirmPassword'] ?? ''),
        ];

        $account = $this->session->get('account');

        $errors = [];

        if ($params['password'] === '') {
            // field is required
            $errors['password'] = 'Kampo deviga';
        } else {
            $verify = $this->passwordsRepository->getByAccountId($account->id);
            if (is_null($verify) || !password_verify($params['password'], $verify->hash)) {
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

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('konto-pasvorto');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                ['errors' => $errors, ...$params],
            );

            return $this->twig->render(
                $resp,
                'layouts/konto.html',
                [
                    'page' => $page,
                    'active' => 'pasvorto',
                ]
            );    
        }

        $hash = password_hash($params['password'], PASSWORD_BCRYPT);
        $this->passwordsRepository->update($account->id, $hash);

        $this->clearAuthState($this->session, $this->sessionManager);

        // You've changed your password. Please log in again.
        $this->session->getFlash()->add('success', 'Vi ŝanĝis vian pasvorton. Bonvolu re-ensaluti.');
        return $this->redirectResponse($resp, '/ensaluti');
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
            $account = $this->accountsRepository->getByEmail($params['email']);
            if (is_null($account)) {
                // avoid further processing and prevent email enumeration!
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

        $otpToken = bin2hex(random_bytes(4));
        $hash = password_hash($otpToken, PASSWORD_BCRYPT);

        $this->otpTokensRepository->create($account->id, $hash);

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
            $account = $this->accountsRepository->getByEmail($token['email']);
            if (is_null($account)) {
                $error = true;
            } else {
                $hash = $this->otpTokensRepository->getByAccountId($account->id);
                if (is_null($hash) || !password_verify($token['otpToken'], $hash)) {
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
            $account = $this->accountsRepository->getByEmail($token['email']);
            if (is_null($account)) {
                $invalidToken = true;
            } else {
                $hash = $this->otpTokensRepository->getByAccountId($account->id);
                if (is_null($hash) || !password_verify($token['otpToken'], $hash)) {
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

        $hash = password_hash($params['password'], PASSWORD_BCRYPT);
        $this->passwordsRepository->update($account->id, $hash);

        $this->otpTokensRepository->deleteByAccountId($account->id);

        // You've changed your password. Please log in again.
        $this->session->getFlash()->add('success', 'Vi ŝanĝis vian pasvorton. Bonvolu ensaluti.');
        return $this->redirectResponse($resp, '/ensaluti');
    }
}
