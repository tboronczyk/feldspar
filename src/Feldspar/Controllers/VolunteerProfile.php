<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\VolunteerProfile as VolunteerProfileEntity;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\VolunteerProfiles as VolunteerProfilesRepository;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class VolunteerProfile extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param AccountsRepository $accountsRepository
     * @param ContentRepository $contentRepository
     * @param VolunteerProfilesRepository $volunteerProfilesRepository
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected AccountsRepository $accountsRepository,
        protected ContentRepository $contentRepository,
        protected VolunteerProfilesRepository $volunteerProfilesRepository,
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

        $profile = $this->volunteerProfilesRepository->getByAccountId($account->id);
        if (is_null($profile)) {
            $profile = new VolunteerProfileEntity();
        }

        $page = $this->contentRepository->fetch('konto-volontulo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'account' => $account,
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
                    'profile' => [...$params],
                ],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $profile = $this->volunteerProfilesRepository->getByAccountId($account->id);
        if (is_null($profile)) {
            // first time edit creates profile
            $profile = new VolunteerProfileEntity(
                accountId: $account->id,
                name: $params['name'],
                website: $params['website'],
                description: $params['description'],
            );
            $this->volunteerProfilesRepository->create($profile);
        } else {
            $profile->name = $params['name'];
            $profile->website = $params['website'];
            $profile->description = $params['description'];
            $this->volunteerProfilesRepository->update($account->id, $profile);
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
        $id = (int)$args['id'];

        $account = $this->accountsRepository->getById($id);
        $profile = $this->volunteerProfilesRepository->getByAccountId($account->id);

        $page = $this->contentRepository->fetch('volontulo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'account' => $account,
                'profile' => $profile,
            ],
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }
}
