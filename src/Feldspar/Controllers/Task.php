<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Task as TaskEntity;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Organizers as OrganizersRepository;
use Feldspar\Repositories\Tasks as TasksRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

class Task extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param ContentRepository $contentRepository
     * @param OrganizersRepository $organizersRepository
     * @param TasksRepository $tasksRepository
     * 
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected ContentRepository $contentRepository,
        protected OrganizersRepository $organizersRepository,
        protected TasksRepository $tasksRepository,
    ) {}

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function get(Request $req, Response $resp, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);

        $task = $this->tasksRepository->getById($id);
        if (is_null($task)) {
            throw new HttpNotFoundException($req);
        }

        $organizer = $this->organizersRepository->getById($task->organizerId);
        if (is_null($organizer)) {
            throw new HttpNotFoundException($req);
        }

        $page = $this->contentRepository->fetch('tasko');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'task' => $task,
                'organizer' => $organizer
            ],
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getListTasks(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $organizer = $this->organizersRepository->getByAccountId($account->id);

        $tasks = $this->tasksRepository->getAllByOrganizerId($organizer->id);

        $page = $this->contentRepository->fetch('konto-taskoj');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            ['tasks' => $tasks],
        );

        return $this->twig->render(
            $resp,
            'layouts/konto.html',
            [
                'page' => $page,
                'active' => 'taskoj',
            ]
        );
    }


    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getCreateTask(Request $req, Response $resp, array $args): Response
    {
        $page = $this->contentRepository->fetch('konto-tasko');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString($page->content, []);

        return $this->twig->render(
            $resp,
            'layouts/konto.html',
            [
                'page' => $page,
                'active' => 'taskoj',
            ]
        );
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function postCreateTask(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');
        $organizer = $this->organizersRepository->getByAccountId($account->id);

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
            $page = $this->contentRepository->fetch('konto-tasko');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'task' => [...$params],
                ],
            );

            return $this->twig->render(
                $resp,
                'layouts/konto.html',
                [
                    'page' => $page,
                    'active' => 'taskoj',
                ]
            );    
        }

        $task = new TaskEntity(
            organizerId: $organizer->id,
            name: $params['name'],
            description: $params['description'],
        );
        $this->tasksRepository->create($task);

        return $this->redirectResponse($resp, '/konto/taskoj');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getUpdateTask(Request $req, Response $resp, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $task = $this->tasksRepository->getById($id);

        $account = $this->session->get('account');
        $organizer = $this->organizersRepository->getByAccountId($account->id);

        if ($task->organizerId !== $organizer->id) {
            // ensure task is owned by user
            throw new HttpNotFoundException($req);
        }

        $page = $this->contentRepository->fetch('konto-tasko');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            ['task' => $task],
        );

        return $this->twig->render(
            $resp,
            'layouts/konto.html',
            [
                'page' => $page,
                'active' => 'taskoj',
            ]
        );

    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function postUpdateTask(Request $req, Response $resp, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        $task = $this->tasksRepository->getById($id);

        $account = $this->session->get('account');
        $organizer = $this->organizersRepository->getByAccountId($account->id);

        if ($task->organizerId !== $organizer->id) {
            // ensure task is owned by user
            throw new HttpNotFoundException($req);
        }

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
            $page = $this->contentRepository->fetch('konto-tasko');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                [
                    'errors' => $errors,
                    'task' => [...$params],
                ],
            );

            return $this->twig->render(
                $resp,
                'layouts/konto.html',
                [
                    'page' => $page,
                    'active' => 'taskoj',
                ]
            );
    
        }

        $task->name = $params['name'];
        $task->description = $params['description'];

        $this->tasksRepository->update($task->id, $task);

        return $this->redirectResponse($resp, '/konto/taskoj');
    }
}
