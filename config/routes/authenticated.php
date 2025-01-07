<?php

declare(strict_types=1);

use Feldspar\Controllers\Auth as AuthController;
use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Middleware\Authorization as AuthorizationMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/update-account', AccountController::class . ':getUpdateAccount');
        $group->post('/update-account', AccountController::class . ':postUpdateAccount');

        $group->get('/update-password', PageController::class . ':get');
        $group->post('/update-password', PasswordController::class . ':postUpdatePassword');

        $group->get('/logout', AuthController::class . ':getLogout');
    })->add(AuthorizationMiddleware::class);
};
