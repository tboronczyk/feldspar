<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Exceptions\AuthenticationException;
use Feldspar\Exceptions\ValidationException;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OtpTokens as OtpTokenRepository;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Workers\Email\SendOtpToken as SendOtpTokenEmailWorker;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Redis;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as SlimTwig;

class Password extends Controller
{
    /**
     * Constructor
     *
     * @param SlimTwig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param Redis $redis
     * @param ContentRepository $content
     * @param OtpTokenRepository $otpTokens
     * @param AccountsRepository $accounts
     * @param array<string, string> $redisConfig
     */
    public function __construct(
        protected SlimTwig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected Redis $redis,
        protected ContentRepository $content,
        protected OtpTokenRepository $otpTokens,
        protected AccountsRepository $accounts,
        protected array $redisConfig,
    ) {
    }

    /**
     * Validation rules for update password form
     *
     * @return array<string,Validatable>
     */
    protected function updatePasswordValidationRules(): array
    {
        return [
            'password' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'newPassword' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'confirmPassword' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
        ];
    }

    /**
     * Validation rules for forgot password form
     *
     * @return array<string,Validatable>
     */
    protected function forgotPasswordValidationRules(): array
    {
        return [
            'email' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
                v::filterVar(FILTER_VALIDATE_EMAIL)->setTemplate('Invalid email address')
            ),
        ];
    }

    /**
     * Validation rules for OTP reset form
     *
     * @return array<string,Validatable>
     */
    protected function otpResetValidationRules(): array
    {
        return [
            'email' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
                v::filterVar(FILTER_VALIDATE_EMAIL)->setTemplate('Email is invalid')
            ),
            'otpToken' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'newPassword' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'confirmPassword' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
        ];
    }

    /**
     * Handle update password form submission
     *
     * @params Request $req
     * @params Response $resp
     * @param array<string, string> $args
     * returns Response
     */
    public function postUpdatePassword(Request $req, Response $resp, array $args): Response
    {
        $acct = $this->session->get('account', null);
        if (!$acct instanceof AccountEntity) {
            throw new AuthenticationException('Account not authenticated');
        }

        $params = $this->paramsFromRequest($req, [
            'password',
            'newPassword',
            'confirmPassword',
        ]);

        $errors = $this->validateData($params, $this->updatePasswordValidationRules());
        try {
            if (count($errors) > 0) {
                throw new ValidationException('Please correct the errors below');
            }

            if ($params['newPassword'] !== $params['confirmPassword']) {
                $errors['confirmPassword'] = 'Confirm new password must match new password';
                throw new ValidationException('Please correct the errors below');
            }

            $verify = $this->accounts->getByEmailAndPassword($acct->email, $params['password']);
            if (is_null($verify)) {
                $errors['password'] = 'Invalid password';
                throw new ValidationException('Please correct the errors below');
            }

            $this->accounts->updatePassword($acct->id, $params['newPassword']);
        } catch (ValidationException $e) {
            $page = $this->content->fetch('update-password');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    ...$params,
                    'errorMessage' => $e->getMessage(),
                    'errors' => $errors,
                ]
            );

            return $this->twig->render($resp, 'layouts/default.html', ['page' => $page]);
        }

        $this->clearAuthState($this->session, $this->sessionManager);

        $this->session->getFlash()->add('success', 'Your password was updated. Please log in again.');

        return $this->redirectResponse($resp, '/login', 303);
    }

    /**
     * Handle forgot password form submission
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function postForgotPassword(Request $req, Response $resp, array $args): Response
    {
        $params = $this->paramsFromRequest($req, [
            'email',
        ]);

        $errors = $this->validateData($params, $this->forgotPasswordValidationRules());

        try {
            if (count($errors) > 0) {
                throw new ValidationException('Please correct the errors below');
            }

            $acct = $this->accounts->getByEmail($params['email']);
            if (is_null($acct)) {
                $errors['email'] = 'Account does not exist';
                throw new ValidationException('Please correct the errors below');
            }
        } catch (ValidationException $e) {
            $page = $this->content->fetch('forgot-password');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    ...$params,
                    'errorMessage' => $e->getMessage(),
                    'errors' => $errors,
                ]
            );

            return $this->twig->render($resp, 'layouts/default.html', ['page' => $page]);
        }

        $token = $this->otpTokens->create($acct->id);

        $queueName = $this->redisConfig['workerQueue'];

        $this->redis->lPush(
            $queueName,
            [
                'worker' => SendOtpTokenEmailWorker::class,
                'args' => [
                    'account' => $acct,
                    'token' => $token,
                ]
            ]
        );

        $this->session->set('forgotPassword_email', $params['email']);

        return $this->redirectResponse($resp, '/otp-reset', 303);
    }

    /**
     * Handle OTP reset page request
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function getOtpReset(Request $req, Response $resp, array $args): Response
    {
        $email = $this->session->get('forgotPassword_email', null);
        if (is_null($email) || !is_string($email)) {
            return $this->redirectResponse($resp, '/forgot-password', 303);
        }
        $this->session->delete('forgotPassword_email');

        $page = $this->content->fetch('otp-reset');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            ['email' => $email]
        );

        return $this->twig->render($resp, 'layouts/default.html', ['page' => $page]);
    }

    /**
     * Handle OTP reset form submission
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function postOtpReset(Request $req, Response $resp, array $args): Response
    {
        $params = $this->paramsFromRequest($req, [
            'email',
            'otpToken',
            'newPassword',
            'confirmPassword',
        ]);

        $errors = $this->validateData($params, $this->otpResetValidationRules());

        try {
            if (count($errors) > 0) {
                throw new ValidationException('Please correct the errors below');
            }

            if ($params['newPassword'] !== $params['confirmPassword']) {
                $errors['confirmPassword'] = 'Confirm password and new password must match';
                throw new ValidationException('Please correct the errors below');
            }

            $acct = $this->accounts->getByEmail($params['email']);
            if (is_null($acct)) {
                $errors['email'] = 'Invalid email address';
                throw new ValidationException('Please correct the errors below');
            }

            $verify = $this->otpTokens->verifyAccountIdAndToken($acct->id, $params['otpToken']);
            if (!$verify) {
                $errors['otpToken'] = 'Invalid or expired OTP token';
                throw new ValidationException('Please correct the errors below');
            }

            $this->accounts->updatePassword($acct->id, $params['newPassword']);
            $this->otpTokens->delete($acct->id);
        } catch (ValidationException $e) {
            $page = $this->content->fetch('otp-reset');
            assert($page !== null);

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    ...$params,
                    'errorMessage' => $e->getMessage(),
                    'errors' => $errors,
                ]
            );

            return $this->twig->render($resp, 'layouts/default.html', ['page' => $page]);
        }

        $this->session->getFlash()->add('success', 'Your password was updated. Please log in again.');

        return $this->redirectResponse($resp, '/login', 303);
    }
}
