<?php

declare(strict_types=1);

use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Authentication as AuthenticationController;
use Feldspar\Controllers\Organizer as OrganizerController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Controllers\Task as TaskController;
use Feldspar\Controllers\Volunteer as VolunteerController;
use Feldspar\Middleware\Authenticated as AuthenticatedMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/konto/konfirmi', AccountController::class . ':getConfirmAccount');
        $group->post('/konto/konfirmi', AccountController::class . ':postConfirmAccount');

        $group->get('/konto', AccountController::class . ':get');

        $group->get('/konto/konto', AccountController::class . ':getUpdateAccount');
        $group->post('/konto/konto', AccountController::class . ':postUpdateAccount');

        $group->get('/konto/pasvorto', PasswordController::class . ':getUpdatePassword');
        $group->post('/konto/pasvorto', PasswordController::class . ':postUpdatePassword');

        $group->get('/konto/volontulo', VolunteerController::class . ':getUpdateProfile');
        $group->post('/konto/volontulo', VolunteerController::class . ':postUpdateProfile');

        $group->get('/konto/organizanto', OrganizerController::class . ':getUpdateProfile');
        $group->post('/konto/organizanto', OrganizerController::class . ':postUpdateProfile');

        $group->get('/konto/taskoj', TaskController::class . ':getListTasks');

        $group->get('/konto/tasko', TaskController::class . ':getCreateTask');
        $group->post('/konto/tasko', TaskController::class . ':postCreateTask');

        $group->get('/konto/tasko/{id:.+}', TaskController::class . ':getUpdateTask');
        $group->post('/konto/tasko/{id:.+}', TaskController::class . ':postUpdateTask');

        $group->get('/elsaluti', AuthenticationController::class . ':logout');

    })->add(AuthenticatedMiddleware::class);
};
