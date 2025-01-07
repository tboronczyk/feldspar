<?php

declare(strict_types=1);

use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Authentication as AuthenticationController;
use Feldspar\Controllers\OAuth2 as OAuth2Controller;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Controllers\Test as TestController;
use Feldspar\Middleware\EnsureUnauthenticated as EnsureUnauthenticatedMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/', PageController::class . ':get');

    $app->get('/uzokondicxoj', PageController::class . ':get');
    $app->get('/privateco', PageController::class . ':get');
    $app->get('/kuketoj', PageController::class . ':get');
    $app->get('/kontakti', PageController::class . ':get');

    $app->get('/test', PageController::class . ':get');

    // the user MUST be unauthenticated for these links
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/dankon', PageController::class . ':get');

        $group->get('/registrigxi', PageController::class . ':get');
        $group->post('/registrigxi', AccountController::class . ':signup');

        $group->get('/registrigxi/facebook', OAuth2Controller::class . ':initiateOAuth2Signup');
        $group->get('/registrigxi/facebook/trakti', OAuth2Controller::class . ':handleOAuth2Signup');

        $group->get('/registrigxi/google', OAuth2Controller::class . ':initiateOAuth2Signup');
        $group->get('/registrigxi/google/trakti', OAuth2Controller::class . ':handleOAuth2Signup');

        $group->get('/ensaluti', PageController::class . ':get');
        $group->post('/ensaluti', AuthenticationController::class . ':login');

        $group->get('/ensaluti/facebook', OAuth2Controller::class . ':initiateOAuth2Login');
        $group->get('/ensaluti/facebook/trakti', OAuth2Controller::class . ':handleOAuth2Login');

        $group->get('/ensaluti/google', OAuth2Controller::class . ':initiateOAuth2Login');
        $group->get('/ensaluti/google/trakti', OAuth2Controller::class . ':handleOAuth2Login');

        $group->get('/pasvorto/forgesita', PageController::class . ':get');
        $group->post('/pasvorto/forgesita', PasswordController::class . ':forgotPassword');
        $group->get('/pasvorto/sendita', PageController::class . ':get');

        $group->get('/pasvorto/restarigi/{token:.*}', PasswordController::class . ':getResetForgotPassword');
        $group->post('/pasvorto/restarigi', PasswordController::class . ':resetForgotPassword');
    })->add(EnsureUnauthenticatedMiddleware::class);
};
