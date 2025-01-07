<?php

declare(strict_types=1);

use Feldspar\Middleware\Session as SessionMiddleware;
use Feldspar\Views\HtmlErrorRenderer;
use Slim\App;
use Slim\Handlers\ErrorHandler;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    $c = $app->getContainer();
    assert($c != null);

    $app->add(TwigMiddleware::create($app, $c->get(Twig::class)));

    $mw = $app->addErrorMiddleware($c->get('config')['debug'], true, true);
    $handler = $mw->getDefaultErrorHandler();
    assert($handler instanceof ErrorHandler);
    $handler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
};
