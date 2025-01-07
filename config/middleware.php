<?php

declare(strict_types=1);

use Feldspar\Views\HtmlErrorRenderer;
use Odan\Session\Middleware\SessionStartMiddleware;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $c = $app->getContainer();
    assert($c !== null);

    $app->addBodyParsingMiddleware();

    $app->addRoutingMiddleware();

    $twig = $c->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));

    $sessionmw = $c->get(SessionStartMiddleware::class);
    $app->add($sessionmw);

    $debug = $c->get('config.debug');
    $mw = $app->addErrorMiddleware($debug, true, true);
    $handler = $mw->getDefaultErrorHandler();
    $handler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
};
