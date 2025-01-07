<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Exceptions\AuthenticationException;
use Feldspar\Exceptions\ValidationException;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Traits\AuthenticationState;
use Feldspar\Traits\ParamsFromRequest;
use Feldspar\Traits\RedirectResponse;
use Feldspar\Traits\ValidateData;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as SlimTwig;

class Account
{
    use AuthenticationState;
    use ParamsFromRequest;
    use RedirectResponse;
    use ValidateData;

    /**
     * Constructor
     *
     * @param SlimTwig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param ContentRepository $content
     * @param AccountsRepository $accounts
     */
    public function __construct(
        protected SlimTwig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected ContentRepository $content,
        protected AccountsRepository $accounts,
    ) {
    }

    /**
     * Validation rules for signup form
     *
     * @return array<string,Validatable>
     */
    protected function signupValidationRules(): array
    {
        return [
            'firstName' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'lastName' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'email' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
                v::filterVar(FILTER_VALIDATE_EMAIL)->setTemplate('Invalid email address')
            ),
            'password' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'confirmPassword' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
        ];
    }

    /**
     * Validation rules for update account form
     *
     * @return array<string,Validatable>
     */
    protected function updateAccountValidationRules(): array
    {
        return [
            'firstName' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'lastName' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
            ),
            'email' => v::allOf(
                v::notEmpty()->setTemplate('Field is required'),
                v::filterVar(FILTER_VALIDATE_EMAIL)->setTemplate('Invalid email address')
            ),
        ];
    }

    /**
     * Handle signup form submission
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function postSignup(Request $req, Response $resp, array $args): Response
    {
        $params = $this->paramsFromRequest($req, [
            'firstName',
            'lastName',
            'email',
            'password',
            'confirmPassword',
        ]);

        $errors = $this->validateData($params, $this->signupValidationRules());

        try {
            if (count($errors) > 0) {
                throw new ValidationException('Please correct the errors below');
            }

            // Check for existing email
            $verify = $this->accounts->getByEmail($params['email']);
            if (!is_null($verify) && $verify->email === $params['email']) {
                $errors['email'] = 'Email already in use';
                throw new ValidationException('Please correct the errors below');
            }

            if ($params['password'] !== $params['confirmPassword']) {
                $errors['confirmPassword'] = 'Confirm password must match password';
                throw new ValidationException('Please correct the errors below');
            }

            $id = $this->accounts->create(AccountEntity::fromRequestParams($params));
            $this->accounts->updatePassword($id, $params['password']);

            $acct = $this->accounts->getById($id);
            assert(!is_null($acct));

            $this->setAuthState($acct, $this->session, $this->sessionManager);
        } catch (ValidationException $e) {
            $page = $this->content->fetch('signup');
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

        return $this->redirectResponse($resp, '/index', 303);
    }

    /**
     * Handle update account request
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function getUpdateAccount(Request $req, Response $resp, array $args): Response
    {
        $acct = $this->session->get('account', null);
        if (!$acct instanceof AccountEntity) {
            throw new AuthenticationException('Account not authenticated');
        }

        $page = $this->content->fetch('update-account');
        assert($page !== null);

        $page->content = $this->twig->fetchFromString(
            $page->content,
            ['account' => $acct]
        );

        return $this->twig->render($resp, 'layouts/default.html', ['page' => $page]);
    }

    /**
     * Handle update account form submission
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function postUpdateAccount(Request $req, Response $resp, array $args): Response
    {
        $acct = $this->session->get('account', null);
        if (!$acct instanceof AccountEntity) {
            throw new AuthenticationException('Account not authenticated');
        }

        $params = $this->paramsFromRequest($req, [
            'firstName',
            'lastName',
            'email',
        ]);

        $errors = $this->validateData($params, $this->updateAccountValidationRules());

        try {
            if (count($errors) > 0) {
                throw new ValidationException('Please correct the errors below');
            }

            // Check for existing email only if email changed
            if ($params['email'] !== $acct->email) {
                $verify = $this->accounts->getByEmail($params['email']);
                if (!is_null($verify)) {
                    $errors['email'] = 'Email already in use';
                    throw new ValidationException('Please correct the errors below');
                }
            }

            $this->accounts->update($acct->id, AccountEntity::fromRequestParams($params));
        } catch (ValidationException $e) {
            $page = $this->content->fetch('update-account');
            assert($page !== null);

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    ...$params,
                    'errorMessage' => $e->getMessage(),
                    'errors' => $errors,
                    'acct' => AccountEntity::fromRequestParams($params)
                ]
            );

            return $this->twig->render($resp, 'layouts/default.html', ['page' => $page]);
        }

        $acct = $this->accounts->getById($acct->id);
        assert(!is_null($acct));

        $this->session->set('account', $acct);

        return $this->redirectResponse($resp, '/index', 303);
    }
}
