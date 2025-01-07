<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Entities\Organizer as OrganizerEntity;
use Feldspar\Entities\Volunteer as VolunteerEntity;
use Feldspar\Entities\OAuth2Account as OAuth2AccountEntity;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OAuth2Accounts as OAuth2AccountsRepository;
use Feldspar\Repositories\Organizers as OrganizersRepository;
use Feldspar\Repositories\Volunteers as VolunteersRepository;
use League\OAuth2\Client\Provider\Facebook as FacebookProvider;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class OAuth2 extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param FacebookProvider $facebookProvider
     * @param GoogleProvider $googleProvider
     * @param AccountsRepository $accountsRepository
     * @param ContentRepository $contentRepository
     * @param OAuth2AccountsRepository $oAuth2AccountsRepository
     * @param OrganizersRepository $organizersRepository
     * @param VolunteersRepository $volunteersRepository
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected FacebookProvider $facebookProvider,
        protected GoogleProvider $googleProvider,
        protected AccountsRepository $accountsRepository,
        protected ContentRepository $contentRepository,
        protected OAuth2AccountsRepository $oAuth2AccountsRepository,
        protected OrganizersRepository $organizersRepository,
        protected VolunteersRepository $volunteersRepository,
    ) {
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function initiateOAuth2Signup(Request $req, Response $resp, array $args): Response
    {
        $path = $req->getUri()->getPath();
        $parts = explode('/', $path);
        $providerName = $parts[2];

        $provider = match ($providerName) {
            'facebook' => $this->facebookProvider,
            'google' => $this->googleProvider,
            default => null,
        };

        if (is_null($provider)) {
            // Unsupported OAuth2 provider. Please try another.
            $this->session->getFlash()->add('error', 'Nesubtenata provizanto de OAuth2. Provu alian elekton.');
            return $this->redirectResponse($resp, '/registrigxi');
        }
        $redirectUri = 'https://' . $_ENV['HTTP_HOST'] . '/registrigxi/' . $providerName . '/trakti';
        $authorizationUrl = $provider->getAuthorizationUrl([
            'redirect_uri' => $redirectUri
        ]);

        $state = $provider->getState();
        $this->session->set('oauth2state', $state);
        return $this->redirectResponse($resp, $authorizationUrl);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function initiateOAuth2Login(Request $req, Response $resp, array $args): Response
    {
        $path = $req->getUri()->getPath();
        $parts = explode('/', $path);
        $providerName = $parts[2];

        $provider = match ($providerName) {
            'facebook' => $this->facebookProvider,
            'google' => $this->googleProvider,
            default => null,
        };

        if (is_null($provider)) {
            // Unsupported OAuth2 provider. Please try another.
            $this->session->getFlash()->add('error', 'Nesubtenata provizanto de OAuth2. Provu alian elekton.');
            return $this->redirectResponse($resp, '/ensaluti');
        }
        $redirectUri = 'https://' . $_ENV['HTTP_HOST'] . '/ensaluti/' . $providerName . '/trakti';
        $authorizationUrl = $provider->getAuthorizationUrl([
            'redirect_uri' => $redirectUri
        ]);

        $state = $provider->getState();
        $this->session->set('oauth2state', $state);
        return $this->redirectResponse($resp, $authorizationUrl);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function handleOAuth2Signup(Request $req, Response $resp, array $args): Response
    {
        $path = $req->getUri()->getPath();
        $parts = explode('/', $path);
        $providerName = $parts[2];

        $provider = match ($providerName) {
            'facebook' => $this->facebookProvider,
            'google' => $this->googleProvider,
            default => null,
        };

        if (is_null($provider)) {
            // Unsupported OAuth2 provider. Please try another.
            $this->session->getFlash()->add('error', 'Nesubtenata provizanto de OAuth2. Provu alian elekton.');
            return $this->redirectResponse($resp, '/registrigxi');
        }

        $queryParams = $req->getQueryParams();
        $params = [
            'state' => trim($queryParams['state'] ?? ''),
            'code' => trim($queryParams['code'] ?? ''),
        ];

        $sessionState = $this->session->get('oauth2state', '');
        $this->session->delete('oauth2state');

        if ($params['state'] === '' || $params['state'] !== $sessionState) {
            // Invalid OAuth2 state. Please try again.
            $this->session->getFlash()->add('error', 'Nevalida stato de OAuth2. Bonvou provi denove.');
            return $this->redirectResponse($resp, '/registrigxi');
        }

        try {
            $redirectUri = 'https://' . $_ENV['HTTP_HOST'] . '/registrigxi/' . $providerName . '/trakti';
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $params['code'],
                'redirect_uri' => $redirectUri,
            ]);

            $user = $provider->getResourceOwner($token);
            $providerId = $user->getId();
            $verify = $this->oAuth2AccountsRepository->getByProviderId($providerName, $providerId);
            if (!is_null($verify)) {
                // Account already exists. Please log in.
                $this->session->getFlash()->add('error', 'Konto jam ekzistas. Bonvolu ensaluti.');
                return $this->redirectResponse($resp, '/ensaluti');
            }

            $email = $user->getEmail();
            $verify = $this->accountsRepository->getByEmail($email);
            if (!is_null($verify)) {
                // Account already exists. Please log in.
                $this->session->getFlash()->add('error', 'Konto jam ekzistas. Bonvolu ensaluti.');
                return $this->redirectResponse($resp, '/ensaluti');
            }

            $i = '';
            do {
                [$username, $_] = explode('@', $email);
                $username .= $i;

                $verify = $this->accountsRepository->getByUsername($username);
                if (!is_null($verify)) {
                    $i = ($i === '') ? 2 : $i + 1;
                }
            } while (!is_null($verify));

            $account = new AccountEntity(
                username: $username,
                email: $email,
                isActive: 1,
            );
            $account->id = $this->accountsRepository->create($account);

            $this->organizersRepository->create(new OrganizerEntity(
                accountId: $account->id,
            ));
    
            $this->volunteersRepository->create(new VolunteerEntity(
                accountId: $account->id,
            ));

            $oAuth2Account = new OAuth2AccountEntity(
                provider: $providerName,
                providerId: $providerId,
                accountId: $account->id,
            );
            $this->oAuth2AccountsRepository->create($oAuth2Account);
        } catch (\Exception $e) {
            // An unexpected error occurred. Please try again.
            $this->session->getFlash()->add('error', 'Neatendita eraro okazis. Bonvolu provi denove.');
            return $this->redirectResponse($resp, '/registrigxi');
        }

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/konto/konfirmi');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function handleOAuth2Login(Request $req, Response $resp, array $args): Response
    {
        $path = $req->getUri()->getPath();
        $parts = explode('/', $path);
        $providerName = $parts[2];

        $provider = match ($providerName) {
            'facebook' => $this->facebookProvider,
            'google' => $this->googleProvider,
            default => null,
        };

        if (is_null($provider)) {
            // Unsupported OAuth2 provider. Please try another.
            $this->session->getFlash()->add('error', 'Nesubtenata provizanto de OAuth2. Provu alian elekton.');
            return $this->redirectResponse($resp, '/ensaluti');
        }

        $queryParams = $req->getQueryParams();
        $params = [
            'state' => trim($queryParams['state'] ?? ''),
            'code' => trim($queryParams['code'] ?? ''),
        ];

        $sessionState = $this->session->get('oauth2state', '');
        $this->session->delete('oauth2state');

        if ($params['state'] === '' || $params['state'] !== $sessionState) {
            // Invalid OAuth2 state. Please try again.
            $this->session->getFlash()->add('error', 'Nevalida stato de OAuth2. Bonvou provi denove.');
            return $this->redirectResponse($resp, '/ensaluti');
        }

        try {
            $redirectUri = 'https://' . $_ENV['HTTP_HOST'] . '/ensaluti/' . $providerName . '/trakti';
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $params['code'],
                'redirect_uri' => $redirectUri,
            ]);

            $user = $provider->getResourceOwner($token);

            $providerId = $user->getId();
            $oAuth2Account = $this->oAuth2AccountsRepository->getByProviderId($providerName, $providerId);
            if (is_null($oAuth2Account)) {
                $account = $this->accountsRepository->getByEmail($user->getEmail());
                if (is_null($account)) {
                    // Account does not exist.
                    $this->session->getFlash()->add('error', 'Konto ne jam ekzistas.');
                    return $this->redirectResponse($resp, '/ensaluti');
                }

                // link existing account
                $oAuth2Account = new OAuth2AccountEntity(
                    provider: $providerName,
                    providerId: $providerId,
                    accountId: $account->id,
                );
                $this->oAuth2AccountsRepository->create($oAuth2Account);
            }

            $account = $this->accountsRepository->getById($oAuth2Account->accountId);
            if (is_null($account)) {
                // An unexpected error occurred. Please try again.
                $this->session->getFlash()->add('error', 'Neatendita eraro okazis. Bonvolu provi denove.');
                return $this->redirectResponse($resp, '/ensaluti');
            }

            if ($account->isActive !== 1) {
                // Account is inactive.
                $this->session->getFlash()->add('error', 'La konto estas neaktiva.');
                return $this->redirectResponse($resp, '/ensaluti');
            }
        } catch (\Exception $e) {
            // An unexpected error occurred. Please try again.
            $this->session->getFlash()->add('error', 'Neatendita eraro okazis. Bonvolu provi denove.');
            return $this->redirectResponse($resp, '/ensaluti');
        }

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/');
    }
}
