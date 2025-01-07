<?php

declare(strict_types=1);

use Feldspar\Controllers\Account as AccountController;
use Feldspar\Controllers\Authentication as AuthenticationController;
use Feldspar\Controllers\OAuth2 as OAuth2Controller;
use Feldspar\Controllers\Page as PageController;
use Feldspar\Controllers\Password as PasswordController;
use Feldspar\Helpers\Db as DbHelper;
use Feldspar\Helpers\JWT as JwtHelper;
use Feldspar\Middleware\Authenticated as AuthenticatedMiddleware;
use Feldspar\Middleware\EnsureUnauthenticated as EnsureUnauthenticatedMiddleware;
use Feldspar\Repositories\Content as ContentRepository;
use Feldspar\Repositories\Countries as CountriesRepository;
use Feldspar\Repositories\EmailContent as EmailContentRepository;
use Feldspar\Repositories\Passwords as PasswordsRepository;
use Feldspar\Services\Account as AccountService;
use Feldspar\Services\Queue as QueueService;
use Feldspar\Services\Token as TokenService;
use Feldspar\Views\HtmlErrorRenderer;
use League\OAuth2\Client\Provider\Facebook as FacebookProvider;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use Feldspar\Workers\Email\ForgotPassword as ForgotPasswordEmailWorker;
use Feldspar\Workers\Email\Welcome as WelcomeEmailWorker;
use Odan\Session\Middleware\SessionStartMiddleware;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface as Container;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Views\Twig;
use Twig\Extension\DebugExtension;
use Twig\TwigFilter;

use function DI\create;
use function DI\factory;
use function DI\get;

return [
    'config.debug' => !(bool)strcasecmp('true', $_ENV['DEBUG']),

    'env' => factory(function (Container $c): array {
        return getenv();
    }),

    PDO::class => factory(function (Container $c): PDO {
        return new PDO(
            $_ENV['DB_DSN'],
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
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
        $redis = new Redis();
        $redis->connect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT']);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        return $redis;
    }),

    SessionManagerInterface::class => function (Container $c): SessionManagerInterface {
        $sessMgr = $c->get(SessionInterface::class);
        return $sessMgr;
    },

    SessionInterface::class => function (Container $c): SessionInterface {
        return new PhpSession([
            'name' => 'feldspar',
            'cookie_samesite' => 'Lax',
            'cookie_secure' => 'true',
            'lifetime' => '86400',
            'cookie_httponly' => 'true',
        ]);
    },

    Twig::class => factory(function (Container $c): Twig {
        $debug = $c->get('config.debug');
        assert(is_bool($debug));

        $twig = Twig::create(
            __DIR__ . '/../templates',
            [
                'debug' => $debug,
                'cache' => $debug ? false : (__DIR__ . '/../var/cache/twig'),
                'auto_reload' => true
            ]
        );
        $env = $c->get('env');
        $twig->getEnvironment()->addGlobal('env', $env);

        $session = $c->get(SessionInterface::class);
        $twig->getEnvironment()->addGlobal('session', $session);

        // Add custom filters
        $twig->getEnvironment()->addFilter(
            new TwigFilter('path_encode', fn(string $path): string =>
            implode('/', array_map('rawurlencode', explode('/', $path))))
        );

        $twig->getEnvironment()->addFilter(
            new TwigFilter('striptags_allowed', fn(string $input): string =>
            strip_tags($input, [
                '<a>',
                '<b>',
                '<br>',
                '<code>',
                '<em>',
                '<i>',
                '<li>',
                '<ol>',
                '<p>',
                '<strong>',
                '<ul>'
            ]))
        );

        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        return $twig;
    }),

    DecoratedResponseFactory::class => function (Container $c): DecoratedResponseFactory {
        return new DecoratedResponseFactory(
            responseFactory: new SlimResponseFactory(),
            streamFactory: new StreamFactory()
        );
    },

    FacebookProvider::class => factory(function (Container $c): FacebookProvider {
        return new FacebookProvider([
            'clientId' => $_ENV['OAUTH2_FACEBOOK_CLIENT_ID'],
            'clientSecret' => $_ENV['OAUTH2_FACEBOOK_CLIENT_SECRET'],
            'graphApiVersion' => 'v21.0',
        ]);
    }),

    GoogleProvider::class => factory(function (Container $c): GoogleProvider {
        return new GoogleProvider([
            'clientId' => $_ENV['OAUTH2_GOOGLE_CLIENT_ID'],
            'clientSecret' => $_ENV['OAUTH2_GOOGLE_CLIENT_SECRET'],
        ]);
    }),

    // Controllers
    AccountController::class => create(AccountController::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            contentRepository: get(ContentRepository::class),
            countriesRepository: get(CountriesRepository::class),
            accountService: get(AccountService::class),
            queueService: get(QueueService::class),
        ),

    AuthenticationController::class => create(AuthenticationController::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            contentRepository: get(ContentRepository::class),
            passwordsRepository: get(PasswordsRepository::class),
            accountService: get(AccountService::class),
        ),

    OAuth2Controller::class => create(OAuth2Controller::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            facebookProvider: get(FacebookProvider::class),
            googleProvider: get(GoogleProvider::class),
            contentRepository: get(ContentRepository::class),
            accountService: get(AccountService::class),
        ),

    PageController::class => create(PageController::class)
        ->constructor(
            twig: get(Twig::class),
            contentRepository: get(ContentRepository::class),
        ),

    PasswordController::class => create(PasswordController::class)
        ->constructor(
            twig: get(Twig::class),
            session: get(SessionInterface::class),
            sessionManager: get(SessionManagerInterface::class),
            jwtHelper: get(JwtHelper::class),
            contentRepository: get(ContentRepository::class),
            passwordsRepository: get(PasswordsRepository::class),
            accountService: get(AccountService::class),
            queueService: get(QueueService::class),
            tokenService: get(TokenService::class),
        ),

    // Helpers
    DbHelper::class => create(DbHelper::class)
        ->constructor(
            pdo: get(PDO::class),
        ),

    JwtHelper::class => factory(function (Container $c): JwtHelper {
        return new JwtHelper($_ENV['JWT_SECRET']);
    }),

    // Middleware
    AuthenticatedMiddleware::class => create(AuthenticatedMiddleware::class)
        ->constructor(
            session: get(SessionInterface::class),
            responseFactory: get(DecoratedResponseFactory::class),
        ),

    EnsureUnauthenticatedMiddleware::class => create(EnsureUnauthenticatedMiddleware::class)
        ->constructor(
            session: get(SessionInterface::class),
            responseFactory: get(DecoratedResponseFactory::class),
        ),

    SessionStartMiddleware::class => create(SessionStartMiddleware::class)
        ->constructor(get(SessionInterface::class)),

    // Repositories
    ContentRepository::class => create(ContentRepository::class)
        ->constructor(
            templatePath: __DIR__ . '/../templates',
        ),

    CountriesRepository::class => create(CountriesRepository::class)
        ->constructor(
            db: get(DbHelper::class),
        ),

    EmailContentRepository::class => create(EmailContentRepository::class)
        ->constructor(
            templatePath: __DIR__ . '/../templates',
        ),

    PasswordsRepository::class => create(PasswordsRepository::class)
        ->constructor(
            db: get(DbHelper::class),
        ),

    // Services
    AccountService::class => create(AccountService::class)
        ->constructor(
            db: get(DbHelper::class),
        ),

    QueueService::class => create(QueueService::class)
        ->constructor(
            redis: get(Redis::class),
        ),

    TokenService::class => create(TokenService::class)
        ->constructor(
            db: get(DbHelper::class),
        ),

    // Views
    HtmlErrorRenderer::class => create(HtmlErrorRenderer::class)
        ->constructor(
            twig: get(Twig::class),
            contentRepository: get(ContentRepository::class),
        ),

    // Workers
    ForgotPasswordEmailWorker::class => create(ForgotPasswordEmailWorker::class)
        ->constructor(
            mailConfig: [
                'dsn' => $_ENV['MAIL_DSN'],
                'systemEmail' => $_ENV['MAIL_SYSTEM_EMAIL'],
                'systemName' => $_ENV['MAIL_SYSTEM_NAME'],
            ],
            twig: get(Twig::class),
            emailContentRepository: get(EmailContentRepository::class),
        ),

    WelcomeEmailWorker::class => create(WelcomeEmailWorker::class)
        ->constructor(
            mailConfig: [
                'dsn' => $_ENV['MAIL_DSN'],
                'systemEmail' => $_ENV['MAIL_SYSTEM_EMAIL'],
                'systemName' => $_ENV['MAIL_SYSTEM_NAME'],
            ],
            twig: get(Twig::class),
            emailContentRepository: get(EmailContentRepository::class),
        ),

    App::class => factory(function (Container $c): App {
        $app = AppFactory::createFromContainer($c);

        // register middleware
        $middleware = (require __DIR__ . '/middleware.php')($app);

        // register routes
        (require __DIR__ . '/routes/authenticated.php')($app);
        (require __DIR__ . '/routes/unauthenticated.php')($app);

        return $app;
    })
];
