<?php

declare(strict_types=1);

use Feldspar\Views\HtmlErrorRenderer;
use Odan\Session\Middleware\SessionStartMiddleware;
use Slim\App;
use Slim\Handlers\ErrorHandler;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $c = $app->getContainer();
    assert($c !== null);

    $app->addBodyParsingMiddleware();

    $sessionmw = $c->get(SessionStartMiddleware::class);
    assert($sessionmw instanceof SessionStartMiddleware);
    $app->add($sessionmw);

    $app->addRoutingMiddleware();

    $twig = $c->get(Twig::class);
    assert($twig instanceof Twig);
    $app->add(TwigMiddleware::create($app, $twig));

    $debug = $c->get('config.debug');
    assert(is_bool($debug));
    $mw = $app->addErrorMiddleware($debug, true, true);
    $handler = $mw->getDefaultErrorHandler();
    assert($handler instanceof ErrorHandler);
    $handler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
};
