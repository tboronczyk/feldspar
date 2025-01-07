<?php

declare(strict_types=1);

use Feldspar\Controllers\Auth as AuthController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Controllers\User as UserController;
use Feldspar\Helpers\DbAccess;
use Feldspar\Middleware\Authorization as AuthorizationMiddleware;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OtpTokens as OtpTokenRepository;
use Feldspar\Repositories\Users as UsersRepository;
use Feldspar\Views\HtmlErrorRenderer;
use Feldspar\Workers\Email\SendOtpToken as SendOtpTokenEmailWorker;
use Odan\Session\PhpSession;
use Psr\Container\ContainerInterface as Container;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Twig\Extension\DebugExtension;
use Twig\TwigFilter;

use function DI\create;
use function DI\env;
use function DI\factory;
use function DI\get;

return [
    'config' => [
        'db' => [
            'dsn' => env('DB_DSN'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ],
        'debug' => !(bool)strcasecmp('true', $_ENV['DEBUG'] ?? 'false'),
        'mail' => [
            'dsn' => env('MAIL_DSN'),
            'systemEmail' => env('MAIL_SYSTEM_EMAIL'),
            'systemName' => env('MAIL_SYSTEM_NAME'),
        ],
        'redis' => [
            'host' => env('REDIS_HOST'),
            'port' => (int)$_ENV['REDIS_PORT'],  // grrr... >:(
            'workerQueue' => 'feldspar.workers'
        ],
        'templates' => [
            'path' => __DIR__ . '/../templates',
            'cache' => __DIR__ . '/../var/cache/twig',
        ],
    ],

    'env' => [
        'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ?? '',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'REQUEST_TIME' => (int)($_SERVER['REQUEST_TIME'] ?? time()),
    ],

    PDO::class => factory(function (Container $c): PDO {
        $config = $c->get('config')['db'];
        return new PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
            ]
        );
    }),

    PhpSession::class => factory(function (): PhpSession {
        $session = new PhpSession();
        $session->start();
        return $session;
    }),

    Twig::class => factory(function (Container $c): Twig {
        $config = $c->get('config');
        $env = $c->get('env');

        $twig = Twig::create(
            $config['templates']['path'],
            [
                'debug' => $config['debug'],
                'cache' => $config['debug'] ? false : $config['templates']['cache'],
                'auto_reload' => true
            ]
        );

        $twig['env'] = $env;
        $twig['session'] = $c->get(PhpSession::class);

        // Add custom filters
        $twig->getEnvironment()->addFilter(
            new TwigFilter('path_encode', fn(string $path): string =>
                implode('/', array_map('rawurlencode', explode('/', $path))))
        );

        if ($config['debug']) {
            $twig->addExtension(new DebugExtension());
        }

        return $twig;
    }),

    Redis::class => factory(function (Container $c): Redis {
        $config = $c->get('config');

        $redis = new Redis();
        $redis->connect($config['redis']['host'], $config['redis']['port']);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        return $redis;
    }),

    DbAccess::class => create(DbAccess::class)
        ->constructor(get(PDO::class)),

    AuthorizationMiddleware::class => create(AuthorizationMiddleware::class)
        ->method('setSession', get(PhpSession::class)),

    HtmlErrorRenderer::class => create(HtmlErrorRenderer::class)
        ->method('setTwig', get(Twig::class))
        ->method('setContentRepository', get(ContentRepository::class)),

    ContentRepository::class => create(ContentRepository::class),

    UsersRepository::class => create(UsersRepository::class)
        ->constructor(get(DbAccess::class)),

    PageController::class => create(PageController::class)
        ->method('setTwig', get(Twig::class))
        ->method('setContentRepository', get(ContentRepository::class)),

    PasswordController::class => create(PasswordController::class)
        ->method('setTwig', get(Twig::class))
        ->method('setConfig', get('config'))
        ->method('setContentRepository', get(ContentRepository::class))
        ->method('setOtpTokenRepository', get(OtpTokenRepository::class))
        ->method('setUsersRepository', get(UsersRepository::class))
        ->method('setRedis', get(Redis::class))
        ->method('setSession', get(PhpSession::class)),

    AuthController::class => create(AuthController::class)
        ->method('setTwig', get(Twig::class))
        ->method('setContentRepository', get(ContentRepository::class))
        ->method('setUsersRepository', get(UsersRepository::class))
        ->method('setSession', get(PhpSession::class)),

    UserController::class => create(UserController::class)
        ->method('setTwig', get(Twig::class))
        ->method('setContentRepository', get(ContentRepository::class))
        ->method('setUsersRepository', get(UsersRepository::class))
        ->method('setSession', get(PhpSession::class)),

    SendOtpTokenEmailWorker::class => create(SendOtpTokenEmailWorker::class)
        ->method('setConfig', get('config')),

    App::class => factory(function (Container $c): App {
        $app = AppFactory::createFromContainer($c);

        // register middleware
        (require __DIR__ . '/middleware.php')($app);

        // register routes
        (require __DIR__ . '/routes/authenticated.php')($app);
        (require __DIR__ . '/routes/unauthenticated.php')($app);

        return $app;
    })
];
