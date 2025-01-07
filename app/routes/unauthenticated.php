<?php

declare(strict_types=1);

use Feldspar\Controllers\Auth as AuthController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Controllers\User as UserController;
use Slim\App;

return function (App $app) {
    $app->get('/', PageController::class . ':get');
    $app->get('/index', PageController::class . ':get');

    $app->get('/terms', PageController::class . ':get');
    $app->get('/privacy', PageController::class . ':get');
    $app->get('/cookies', PageController::class . ':get');
    $app->get('/contact', PageController::class . ':get');

    $app->get('/signup', PageController::class . ':get');
    $app->post('/signup', UserController::class . ':postSignup');

    $app->get('/login', PageController::class . ':get');
    $app->post('/login', AuthController::class . ':postLogin');

    $app->get('/forgot-password', PageController::class . ':get');
    $app->post('/forgot-password', PasswordController::class . ':postForgotPassword');
    $app->post('/otp-reset', PasswordController::class . ':postOtpReset');
};
