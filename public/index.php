<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\App;

/** @var App<ContainerInterface> $app */
$app = (require_once __DIR__ . '/../config/bootstrap.php');
$app->run();
