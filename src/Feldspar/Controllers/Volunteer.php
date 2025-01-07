<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Volunteers as VolunteersRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

class Volunteer extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param AccountsRepository $accountsRepository
     * @param ContentRepository $contentRepository
     * @param VolunteersRepository $volunteersRepository
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected AccountsRepository $accountsRepository,
        protected ContentRepository $contentRepository,
        protected VolunteersRepository $volunteersRepository,
    ) {
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getUpdateProfile(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $volunteer = $this->volunteersRepository->getByAccountId($account->id);
        if (is_null($volunteer)) {
            throw new HttpNotFoundException($req);
        }

        $page = $this->contentRepository->fetch('konto-volontulo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'account' => $account,
                'volunteer' => $volunteer,
            ],
        );

        return $this->twig->render(
            $resp,
            'layouts/konto.html',
            [
                'page' => $page,
                'active' => 'volontulo',
            ]
        );
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function postUpdateProfile(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $body = (array)$req->getParsedBody();

        $params = [
            'name' => trim($body['name'] ?? ''),
            'description' => trim($body['description'] ?? ''),
        ];

        $errors = [];

        if ($params['name'] === '') {
            // field is required
            $errors['name'] = 'Kampo deviga';
        }

        if ($params['description'] === '') {
            // field is required
            $errors['description'] = 'Kampo deviga';
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('konto-volontulo');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'account' => $account,
                    'volunteer' => [...$params],
                ],
            );

            return $this->twig->render(
                $resp,
                'layouts/konto.html',
                [
                    'page' => $page,
                    'active' => 'volontulo',
                ]
            );    
        }

        $volunteer = $this->volunteersRepository->getByAccountId($account->id);
        if (is_null($volunteer)) {
            throw new HttpNotFoundException($req);
        }
                                
        $volunteer->name = $params['name'];
        $volunteer->description = $params['description'];
        $this->volunteersRepository->update($volunteer->id, $volunteer);
    
        return $this->redirectResponse($resp, '/konto');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getProfile(Request $req, Response $resp, array $args): Response
    {
        $id = (int)$args['id'];

        $volunteer = $this->volunteersRepository->getById($id);
        if (is_null($volunteer)) {
            throw new HttpNotFoundException($req);
        }

        $account = $this->accountsRepository->getById($volunteer->accountId);
        if (is_null($account)) {
            throw new HttpNotFoundException($req);
        }

        $page = $this->contentRepository->fetch('volontulo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'account' => $account,
                'volunteer' => $volunteer,
            ],
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }
}
