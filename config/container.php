<?php

declare(strict_types=1);

use Feldspar\Controllers\Auth as AuthController;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Controllers\Account as AccountController;
use Feldspar\Helpers\DbAccess;
use Feldspar\Middleware\Authorization as AuthorizationMiddleware;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\OtpTokens as OtpTokenRepository;
use Feldspar\Repositories\Accounts as AccountsRepository;
use Feldspar\Views\HtmlErrorRenderer;
use Feldspar\Workers\Email\SendOtpToken as SendOtpTokenEmailWorker;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Odan\Session\Middleware\SessionStartMiddleware;
use Psr\Container\ContainerInterface as Container;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Twig\Extension\DebugExtension;
use Twig\TwigFilter;

use function DI\create;
use function DI\factory;
use function DI\get;

return [
    'config.db' => factory(function (Container $c): array {
        return [
            'dsn' => getenv('DB_DSN'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
        ];
    }),

    'config.debug' => factory(function (Container $c): bool {
        $debug = getenv('DEBUG');
        assert(is_string($debug));

        return !(bool)strcasecmp('true', $debug);
    }),

    'config.mail' => factory(function (Container $c): array {
        return [
            'dsn' => getenv('MAIL_DSN'),
            'systemEmail' => getenv('MAIL_SYSTEM_EMAIL'),
            'systemName' => getenv('MAIL_SYSTEM_NAME'),
        ];
    }),

    'config.paths' => factory(function (Container $c): array {
        return [
            'templates' => __DIR__ . '/../templates',
            'cache' => __DIR__ . '/../var/cache/twig',
        ];
    }),

    'config.redis' => factory(function (Container $c): array {
        return [
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'workerQueue' => 'feldspar.workers'
        ];
    }),

    'config.session' => factory(function (Container $c): array {
        return [
            'name' => 'feldspar',
            'cookie_samesite' => 'Lax',
            'cookie_secure' => 'true',
            'lifetime' => '43200',
            'cookie_httponly' => 'true',
        ];
    }),

    'env' => factory(function (Container $c): array {
        return getenv();
    }),

    PDO::class => factory(function (Container $c): PDO {
        /** @var array<string, string> $config */
        $config = $c->get('config.db');

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

    Redis::class => factory(function (Container $c): Redis {
        /** @var array<string, string> $config */
        $config = $c->get('config.redis');

        $redis = new Redis();
        $redis->connect($config['host'], (int)$config['port']);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        return $redis;
    }),

    SessionManagerInterface::class => function (Container $c): SessionManagerInterface {
        /** @var SessionManagerInterface $sessMgr */
        $sessMgr = $c->get(SessionInterface::class);
        return $sessMgr;
    },

    SessionInterface::class => function (Container $c): SessionInterface {
        /** @var array<string, string> $config */
        $config = $c->get('config.session');
        return new PhpSession($config);
    },

    Twig::class => factory(function (Container $c): Twig {
        /** @var bool $debug */
        $debug = $c->get('config.debug');
        /** @var array<string, string> $paths */
        $paths = $c->get('config.paths');

        $twig = Twig::create(
            $paths['templates'],
            [
                'debug' => $debug,
                'cache' => $debug ? false : $paths['cache'],
                'auto_reload' => true
            ]
        );
        $env = $c->get('env');
        $twig->getEnvironment()->addGlobal('env', $env);

        /** @var SessionInterface $session */
        $session = $c->get(SessionInterface::class);
        $twig->getEnvironment()->addGlobal('session', $session);

        // Add custom filters
        $twig->getEnvironment()->addFilter(
            new TwigFilter('path_encode', fn(string $path): string =>
            implode('/', array_map('rawurlencode', explode('/', $path))))
        );

        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        return $twig;
    }),

    // Controllers
    AuthController::class => create(AuthController::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            content: get(ContentRepository::class),
            accounts: get(AccountsRepository::class),
        ),

    PageController::class => create(PageController::class)
        ->constructor(
            twig: get(Twig::class),
            content: get(ContentRepository::class),
        ),

    PasswordController::class => create(PasswordController::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            redis: get(Redis::class),
            content: get(ContentRepository::class),
            otpTokens: get(OtpTokenRepository::class),
            accounts: get(AccountsRepository::class),
            redisConfig: get('config.redis'),
        ),

    AccountController::class => create(AccountController::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            content: get(ContentRepository::class),
            accounts: get(AccountsRepository::class),
        ),

    // Helpers
    DbAccess::class => create(DbAccess::class)
        ->constructor(
            pdo: get(PDO::class),
        ),

    // Middleware
    AuthorizationMiddleware::class => create(AuthorizationMiddleware::class)
        ->constructor(get(SessionInterface::class)),

    SessionStartMiddleware::class => create(SessionStartMiddleware::class)
        ->constructor(get(SessionInterface::class)),

    // Repositories
    ContentRepository::class => create(ContentRepository::class)
        ->constructor(
            pathsConfig: get('config.paths'),
        ),

    OtpTokenRepository::class => create(OtpTokenRepository::class)
        ->constructor(
            db: get(DbAccess::class),
        ),

    AccountsRepository::class => create(AccountsRepository::class)
        ->constructor(
            db: get(DbAccess::class),
        ),

    // Views
    HtmlErrorRenderer::class => create(HtmlErrorRenderer::class)
        ->constructor(
            twig: get(Twig::class),
            content: get(ContentRepository::class),
        ),

    // Workers
    SendOtpTokenEmailWorker::class => create(SendOtpTokenEmailWorker::class)
        ->constructor(
            mailConfig: get('config.mail'),
        ),

    App::class => factory(function (Container $c): App {
        $app = AppFactory::createFromContainer($c);

        // register middleware
        $middleware = (require __DIR__ . '/middleware.php')($app); // @phpstan-ignore callable.nonCallable

        // register routes
        (require __DIR__ . '/routes/authenticated.php')($app);   // @phpstan-ignore callable.nonCallable
        (require __DIR__ . '/routes/unauthenticated.php')($app); // @phpstan-ignore callable.nonCallable

        return $app;
    })
];
