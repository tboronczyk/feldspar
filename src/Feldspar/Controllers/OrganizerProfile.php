<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\OrganizerProfile as OrganizerProfileEntity;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OrganizerProfiles as OrganizerProfilesRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class OrganizerProfile extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param ContentRepository $contentRepository
     * @param OrganizerProfilesRepository $organizerProfilesRepository
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected ContentRepository $contentRepository,
        protected OrganizerProfilesRepository $organizerProfilesRepository,
    ) {}

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function getUpdateProfile(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');

        $profile = $this->organizerProfilesRepository->getByAccountId($account->id);
        if (is_null($profile)) {
            $profile = new organizerProfileEntity();
        }

        $page = $this->contentRepository->fetch('konto-organizanto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'profile' => $profile,
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
    public function postUpdateProfile(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');
        
        $body = (array)$req->getParsedBody();

        $params = [
            'name' => trim($body['name'] ?? ''),
            'website' => trim($body['website'] ?? ''),
            'email' => trim($body['email'] ?? ''),
            'mission' => trim($body['mission'] ?? ''),
            'description' => trim($body['description'] ?? ''),
        ];

        $errors = [];

        if ($params['name'] === '') {
            // field is required
            $errors['name'] = 'Kampo deviga';
        }

        if ($params['website'] === '') {
            // field is required
            $errors['website'] = 'Kampo deviga';
        } elseif (filter_var($params['website'], FILTER_VALIDATE_URL) === false) {
            // malformed url
            $errors['website'] = 'Nevalida adreso de retejo';
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed url
            $errors['email'] = 'Nevalida retpoŝtadreso';
        }

        if ($params['mission'] === '') {
            // field is required
            $errors['mission'] = 'Kampo deviga';
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
                    'profile' => [...$params],
                ],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $profile = $this->organizerProfilesRepository->getByAccountId($account->id);
        if (is_null($profile)) {
            // first time edit creates profile
            $profile = new organizerProfileEntity(
                accountId: $account->id,
                name: $params['name'],
                email: $params['email'],
                website: $params['website'],
                mission: $params['mission'],
                description: $params['description'],
            );
            $this->organizerProfilesRepository->create($profile);
        } else {
            $profile->name = $params['name'];
            $profile->email = $params['email'];
            $profile->website = $params['website'];
            $profile->mission = $params['mission'];
            $profile->description = $params['description'];
            $this->organizerProfilesRepository->update($account->id, $profile);
        }
        
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
        $orgId = (int)$args['id'];

        $profile = $this->organizerProfilesRepository->getById($orgId);

        $page = $this->contentRepository->fetch('organizanto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            ['profile' => $profile],
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }
}
