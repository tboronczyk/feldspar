<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Exceptions\ValidationException;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;
use Slim\Views\Twig as SlimTwig;

class Auth extends Controller
{
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
     * Validation rules for login form
     *
     * @return array<string,Validatable>
     */
    protected function loginValidationRules(): array
    {
        return [
            'email' => v::allOf(
                v::notEmpty()->setTemplate('This field is required'),
                v::filterVar(FILTER_VALIDATE_EMAIL)->setTemplate('Invalid email address')
            ),
            'password' => v::allOf(
                v::notEmpty()->setTemplate('This field is required'),
            ),
        ];
    }

    /**
     * Handle login form submission
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function postLogin(Request $req, Response $resp, array $args): Response
    {
        $params = $this->paramsFromRequest($req, [
            'email',
            'password',
        ]);

        $errors = $this->validateData($params, $this->loginValidationRules());

        try {
            if (count($errors) > 0) {
                throw new ValidationException('Please correct the errors below');
            }

            $acct = $this->accounts->getByEmailAndPassword(
                $params['email'],
                $params['password']
            );

            if (is_null($acct)) {
                throw new ValidationException('Invalid email address or password');
            }
            if ($acct->isActive !== 1) {
                throw new ValidationException('Account is not active');
            }
        } catch (ValidationException $e) {
            $page = $this->content->fetch('login');
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

        $this->setAuthState($acct, $this->session, $this->sessionManager);

        return $this->redirectResponse($resp, '/index', 303);
    }

    /**
     * Handle log out request
     *
     * @param Request $req
     * @param Response $resp
     * @param array<string, string> $args
     * @return Response
     */
    public function getLogout(Request $req, Response $resp, array $args): Response
    {
        $this->clearAuthState($this->session, $this->sessionManager);

        return $this->redirectResponse($resp, '/login');
    }
}
