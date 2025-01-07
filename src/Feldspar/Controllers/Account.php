<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Entities\Account as AccountEntity;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Countries as CountriesRepository;
use Feldspar\Services\Account as AccountService;
use Feldspar\Services\Queue as QueueService;
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
     * @param ContentRepository $contentRepository
     * @param CountriesRepository $countriesRepository
     * @param AccountService $accountService
     * @param QueueService $queueService
     */
    public function __construct(
        protected Twig $twig,
        protected Session $session,
        protected SessionManager $sessionManager,
        protected ContentRepository $contentRepository,
        protected CountriesRepository $countriesRepository,
        protected AccountService $accountService,
        protected QueueService $queueService,
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
            // plausable sounding fields for spam prevention
            'gender' => trim($body['gender'] ?? ''),
            'confirmEmail' => trim($body['confirmEmail'] ?? ''),
            'referrer' => trim($body['referrer'] ?? ''),
        ];

        //  immediately honeypot the user if any spam fields are received with a value
        if ($params['gender'] !== '' || $params['confirmEmail'] !== '' || $params['referrer'] !== '') {
            return $this->redirectResponse($resp, '/dankon');
        }

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
            $verify = $this->accountService->verifyUsername($params['username']);
            if ($verify->result) {
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
            $verify = $this->accountService->verifyEmail($params['email']);
            if ($verify->result) {
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
                [
                    ...$params,
                    'errors' => $errors,
                ],
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

        $account->id = $this->accountService->create($account, $params['password']);
        $this->queueService->queueWelcomeEmail($account);

        $this->setAuthState($account, $this->session, $this->sessionManager);
        return $this->redirectResponse($resp, '/konto');
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function get(Request $req, Response $resp, array $args): Response
    {
        $sessAcct = $this->session->get('account');
        $account = $this->accountService->getById($sessAcct['id']);
        assert($account !== null);

        $countries = $this->countriesRepository->get();

        $authTypes = $this->accountService->getAuthTypes($account->id);
        $enablePasswordChange = in_array('password', $authTypes, true);

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
                    'countries' => $countries,
                ],
                'password' => [
                    'enablePasswordChange' => $enablePasswordChange
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
        $sessAcct = $this->session->get('account');
        $account = $this->accountService->getById($sessAcct['id']);
        assert($account !== null);

        $countries = $this->countriesRepository->get();

        $page = $this->contentRepository->fetch('konto/profilo');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString(
            $page->content,
            [
                'account' => [
                    ...(array)$account,
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
        $sessAcct = $this->session->get('account');
        $account = $this->accountService->getById($sessAcct['id']);
        assert($account !== null);

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
            $verify = $this->accountService->verifyUsername($params['username']);
            if ($verify->result && $verify->accountId !== $account->id) {
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
            $verify = $this->accountService->verifyEmail($params['email']);
            if ($verify->result && $verify->accountId !== $account->id) {
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
            $account->profile = $params['profile'];

            $this->accountService->update($account->id, $account);

            $this->setAuthState($account, $this->session, $this->sessionManager);

            // changes saved successfully
            $toast['message'] = 'Personaj informoj sukcese ŝanĝiĝis.';
            $toast['type'] = 'success';
        }

        $countries = $this->countriesRepository->get();

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
        $sessAcct = $this->session->get('account');
        $accountId = $sessAcct['id'];

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

                    if ($width !== $height) {
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
                            $targetPath = getcwd() . '/avatars/' . $accountId . '.png';
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
                    'accountId' => $accountId,
                    'status' => $toast['type'],
                ],
            ],
        );

        return $this->twig->render($resp, 'layouts/partial.html', ['page' => $page]);
    }
}
