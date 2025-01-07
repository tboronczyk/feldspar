<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Countries as CountriesRepository;
use Feldspar\Repositories\Passwords as PasswordsRepository;
use Feldspar\Services\Avatar as AvatarService;
use Odan\Session\SessionInterface as Session;
use Odan\Session\SessionManagerInterface as SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class Account extends Controller
{
    /**
     * @param Twig $twig
     * @param Session $session
     * @param SessionManager $sessionManager
     * @param AccountsRepository $accountsRepository
     * @param ContentRepository $contentRepository
     * @param CountriesRepository $countriesRepository
     * @param PasswordsRepository $passwordsRepository
     * @param AvatarService $avatarService
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected AccountsRepository $accountsRepository,
        protected ContentRepository $contentRepository,
        protected CountriesRepository $countriesRepository,
        protected PasswordsRepository $passwordsRepository,
        protected AvatarService $avatarService,
    ) {
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function signup(Request $req, Response $resp, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $params = [
            'firstName' => trim($body['firstName'] ?? ''),
            'lastName' => trim($body['lastName'] ?? ''),
            'username' => trim($body['username'] ?? ''),
            'email' => trim($body['email'] ?? ''),
            'password' => trim($body['password'] ?? ''),
            'confirmPassword' => trim($body['confirmPassword'] ?? ''),
        ];

        $errors = [];

        if ($params['firstName'] === '') {
            // field is required
            $errors['firstName'] = 'Kampo deviga';
        }

        if ($params['lastName'] === '') {
            // field is required
            $errors['lastName'] = 'Kampo deviga';
        }

        if ($params['username'] === '') {
            // field is required
            $errors['username'] = 'Kampo deviga';
        } else {
            $verify = $this->accountsRepository->getByUsername($params['username']);
            if (!is_null($verify)) {
                // username already in use
                $errors['username'] = 'Uzantnomo jam uzita';
            }
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        } else {
            $verify = $this->accountsRepository->getByEmail($params['email']);
            if (!is_null($verify)) {
                // email address already in use
                $errors['email'] = 'Retpoŝtadreso jam uzita';
            }
        }

        if ($params['password'] === '') {
            // field is required
            $errors['password'] = 'Kampo deviga';
        }

        if ($params['confirmPassword'] === '') {
            // field is required
            $errors['confirmPassword'] = 'Kampo deviga';
        } elseif ($params['password'] !== $params['confirmPassword']) {
            // confirm password does not match password
            $errors['confirmPassword'] = 'Ripetita pasvorto ne kongruas kun pasvorto';
        }

        if (count($errors) > 0) {
            $page = $this->contentRepository->fetch('registrigxi');
            assert(!is_null($page));

            $page->content = $this->twig->fetchFromString(
                $page->content,
                ['errors' => $errors, ...$params],
            );

            return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
        }

        $account = new AccountEntity(
            firstName: $params['firstName'],
            lastName: $params['lastName'],
            username: $params['username'],
            email: $params['email'],
            isActive: 1,
        );
        $account->id = $this->accountsRepository->create($account);

        $hash = password_hash($params['password'], PASSWORD_BCRYPT);
        $this->passwordsRepository->update($account->id, $hash);

        $this->avatarService->createDefault($account->id);

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function get(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');
        $profile = $this->accountsRepository->getAccountProfile($account->id);
        $countries = $this->countriesRepository->get();

        $page = $this->contentRepository->fetch('konto');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'avatar' => [
                    'accountId' => $account->id,
                ],
                'account' => [
                    ...(array)$account,
                    'profile' => $profile,
                    'countries' => $countries,
                ],
            ]
        );

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
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
        $profile = $this->accountsRepository->getAccountProfile($account->id);
        $countries = $this->countriesRepository->get();

        $page = $this->contentRepository->fetch('konto/profilo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'account' => [
                    ...(array)$account,
                    'profile' => $profile,
                    'countries' => $countries,
                ],
            ]
        );

        return $this->twig->render($resp, 'layouts/partial.html', ['page' => $page]);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function updateProfile(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');
        $countries = $this->countriesRepository->get();

        $body = (array)$req->getParsedBody();

        $params = [
            'firstName' => trim($body['firstName'] ?? ''),
            'lastName' => trim($body['lastName'] ?? ''),
            'username' => trim($body['username'] ?? ''),
            'country' => trim($body['country'] ?? ''),
            'email' => trim($body['email'] ?? ''),
            'profile' => trim($body['profile'] ?? ''),
        ];

        $errors = [];

        if ($params['firstName'] === '') {
            // field is required
            $errors['firstName'] = 'Kampo deviga';
        }

        if ($params['lastName'] === '') {
            // field is required
            $errors['lastName'] = 'Kampo deviga';
        }

        if ($params['username'] === '') {
            // field is required
            $errors['username'] = 'Kampo deviga';
        } else {
            $verify = $this->accountsRepository->getByUsername($params['username']);
            if (!is_null($verify) && $verify->id !== $account->id) {
                // username already in use
                $errors['username'] = 'Uzantnomo jam uzita';
            }
        }

        if ($params['email'] === '') {
            // field is required
            $errors['email'] = 'Kampo deviga';
        } elseif (filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            // malformed email address
            $errors['email'] = 'Nevalida retpoŝtadreso';
        } else {
            $verify = $this->accountsRepository->getByEmail($params['email']);
            if (!is_null($verify) && $verify->id !== $account->id) {
                // email address already in use
                $errors['email'] = 'Retpoŝtadreso jam uzita';
            }
        }

        $success = count($errors) === 0;
        $toast = [];

        if ($success) {
            $account->firstName = $params['firstName'];
            $account->lastName = $params['lastName'];
            $account->username = $params['username'];
            $account->email = $params['email'];
            $account->country = $params['country'];

            $this->accountsRepository->update($account->id, $account);
            $this->accountsRepository->updateAccountProfile($account->id, $params['profile']);

            $this->setAuthState($account, $this->session, $this->sessionManager);

            // changes saved successfully
            $toast['message'] = 'Personaj informoj sukcese ŝanĝiĝis.';
            $toast['type'] = 'success';
        }

        $page = $this->contentRepository->fetch('konto/profilo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'errors' => $errors,
                'account' => [
                    ...$params,
                    'countries' => $countries,
                ],
                'toast' => $toast
            ],
        );

        return $this->twig->render($resp, 'layouts/partial.html', ['page' => $page]);
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function updateAvatar(Request $req, Response $resp, array $args): Response
    {
        $account = $this->session->get('account');
        $error = null;
        $uploadedFiles = $req->getUploadedFiles();

        if (!isset($uploadedFiles['avatar'])) {
            // no file uploaded
            $error = 'Neniu bildo alŝutita.';
        } else {
            $avatar = $uploadedFiles['avatar'];

            // Check for upload errors
            if ($avatar->getError() !== UPLOAD_ERR_OK) {
                // error occured
                $error = 'Eraro okazis.';
            } else {
                // Get image info
                $tmpFile = $avatar->getStream()->getMetadata('uri');
                $imageInfo = getimagesize($tmpFile);

                if ($imageInfo === false) {
                    // Invalid image format
                    $error = 'Nevalida bildoformato.';
                } else {
                    // Check if image dimensions are multiples of 128
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    $mime = $imageInfo['mime'];

                    if ($width % 128 !== 0 || $height % 128 !== 0) {
                        // incorrect dimensions
                        $error = 'Malĝustaj dimensioj.';
                    } else {
                        // Create image resource based on mime type
                        $srcImage = match ($mime) {
                            'image/jpeg' => imagecreatefromjpeg($tmpFile),
                            'image/png' => imagecreatefrompng($tmpFile),
                            'image/gif' => imagecreatefromgif($tmpFile),
                            default => false
                        };

                        if ($srcImage === false) {
                            // Invalid image format
                            $error = 'Nevalida bildoformato.';
                        } else {
                            // Resize if larger than 256x256
                            if ($width > 256 || $height > 256) {
                                $newImage = imagecreatetruecolor(256, 256);
                                imagealphablending($newImage, false);
                                imagesavealpha($newImage, true);
                                imagecopyresampled(
                                    $newImage,
                                    $srcImage,
                                    0,
                                    0,
                                    0,
                                    0,
                                    256,
                                    256,
                                    $width,
                                    $height
                                );
                                imagedestroy($srcImage);
                                $srcImage = $newImage;
                            }

                            // Save as PNG
                            $targetPath = getcwd() . '/avatars/' . $account->id . '.png';
                            if (!imagepng($srcImage, $targetPath)) {
                                // error occured
                                $error = 'Eraro okazis.';
                            }
                            imagedestroy($srcImage);
                        }
                    }
                }

                // Clean up temporary file
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
        }

        $toast = [];
        if (is_null($error)) {
            $toast['message'] = 'Profilbildo sukcese ŝanĝiĝis.';
            $toast['type'] = 'success';
        } else {
            $toast['message'] = $error . ' Profilbildo restas neŝanĝita.';
            $toast['type'] = 'error';
        }

        $page = $this->contentRepository->fetch('konto/bildo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'toast' => $toast,
                'avatar' => [
                    'accountId' => $account->id,
                ],
            ],
        );

        return $this->twig->render($resp, 'layouts/partial.html', ['page' => $page]);
    }
}
