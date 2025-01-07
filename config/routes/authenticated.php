<?php

declare(strict_types=1);

use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Authentication as AuthenticationController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Middleware\Authenticated as AuthenticatedMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/pasvorto', PageController::class . ':get');
        $group->post('/pasvorto', PasswordController::class . ':updatePassword');

        $group->get('/konto', AccountController::class . ':getUpdateAccount');
        $group->post('/konto', AccountController::class . ':postUpdateAccount');

        $group->get('/konto/konfirmi', AccountController::class . ':getConfirmAccount');
        $group->post('/konto/konfirmi', AccountController::class . ':postConfirmAccount');

        $group->get('/elsaluti', AuthenticationController::class . ':logout');
    })->add(AuthenticatedMiddleware::class);
};
