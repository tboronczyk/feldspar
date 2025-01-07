<?php

declare(strict_types=1);

use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Authentication as AuthenticationController;
use Feldspar\Controllers\OrganizerProfile as OrganizerProfileController;
use Feldspar\Controllers\VolunteerProfile as VolunteerProfileController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Entities\VolunteerProfile;
use Feldspar\Middleware\Authenticated as AuthenticatedMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/konto/konfirmi', AccountController::class . ':getConfirmAccount');
        $group->post('/konto/konfirmi', AccountController::class . ':postConfirmAccount');

        $group->get('/konto', PageController::class . ':get');

        $group->get('/konto/konto', AccountController::class . ':getUpdateAccount');
        $group->post('/konto/konto', AccountController::class . ':postUpdateAccount');

        $group->get('/konto/pasvorto', PageController::class . ':get');
        $group->post('/konto/pasvorto', PasswordController::class . ':postUpdatePassword');

        $group->get('/konto/volontulo', VolunteerProfileController::class . ':getUpdateProfile');
        $group->post('/konto/volontulo', VolunteerProfileController::class . ':postUpdateProfile');

        $group->get('/konto/organizanto', OrganizerProfileController::class . ':getUpdateProfile');
        $group->post('/konto/organizanto', OrganizerProfileController::class . ':postUpdateProfile');

        $group->get('/elsaluti', AuthenticationController::class . ':logout');

        $group->get('/volontulo/{id:.+}', VolunteerProfileController::class . ':getProfile');
        $group->get('/organizanto/{id:.+}', OrganizerProfileController::class . ':getProfile');

        $group->get('/test', PageController::class . ':get');

    })->add(AuthenticatedMiddleware::class);
};
