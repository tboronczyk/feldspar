<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Organizers as OrganizersRepository;
use Feldspar\Repositories\Tasks as TasksRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

class Organizer extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param ContentRepository $contentRepository
     * @param OrganizersRepository $organizersRepository
     * @param TasksRepository $tasksRepository
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected ContentRepository $contentRepository,
        protected OrganizersRepository $organizersRepository,
        protected TasksRepository $tasksRepository,
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

        $organizer = $this->organizersRepository->getByAccountId($account->id);
        if (is_null($organizer)) {
            throw new HttpNotFoundException($req);
        }

        $page = $this->contentRepository->fetch('konto-organizanto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'organizer' => $organizer,
            ],
        );

        return $this->twig->render(
            $resp,
            'layouts/konto.html',
            [
                'page' => $page,
                'active' => 'organizanto',
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
            'email' => trim($body['email'] ?? ''),
            'description' => trim($body['description'] ?? ''),
        ];

        $errors = [];

        if ($params['name'] === '') {
            // field is required
            $errors['name'] = 'Kampo deviga';
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed url
            $errors['email'] = 'Nevalida retpoŝtadreso';
        }

        if ($params['description'] === '') {
            // field is required
            $errors['description'] = 'Kampo deviga';
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('konto-organizanto');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'organizer' => [...$params],
                ],
            );

            return $this->twig->render(
                $resp,
                'layouts/konto.html',
                [
                    'page' => $page,
                    'active' => 'organizanto',
                ]
            );    
        }

        $organizer = $this->organizersRepository->getByAccountId($account->id);
        if (is_null($organizer)) {
            throw new HttpNotFoundException($req);
        }

        $organizer->name = $params['name'];
        $organizer->email = $params['email'];
        $organizer->description = $params['description'];
        $this->organizersRepository->update($organizer->id, $organizer);

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

        $organizer = $this->organizersRepository->getById($id);
        if (is_null($organizer)) {
            throw new HttpNotFoundException($req);
        }

        $tasks = $this->tasksRepository->getTopByOrganizerId($organizer->id);

        $page = $this->contentRepository->fetch('organizanto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'organizer' => $organizer,
                'tasks' => $tasks
            ],
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }
}
