<?php

declare(strict_types=1);

use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Authentication as AuthenticationController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Middleware\Authenticated as AuthenticatedMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/konto', AccountController::class . ':get');

        $group->get('/konto/profilo', AccountController::class . ':getUpdateProfile');
        $group->post('/konto/profilo', AccountController::class . ':updateProfile');

        $group->post('/konto/bildo', AccountController::class . ':updateAvatar');

        $group->get('/konto/pasvorto', PasswordController::class . ':getUpdatePassword');
        $group->post('/konto/pasvorto', PasswordController::class . ':updatePassword');

        $group->get('/elsaluti', AuthenticationController::class . ':logout');
    })->add(AuthenticatedMiddleware::class);
};
