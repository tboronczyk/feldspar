<?php

declare(strict_types=1);

namespace Feldspar\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Odan\Session\SessionInterface as Session;
use Slim\Exception\HttpUnauthorizedException;

class Authorization implements MiddlewareInterface
{
    /**
     * Constructor
     *
     * @param Session $session
     */
    public function __construct(
        protected Session $session,
    ) {
    }

    /**
     * Block unauthenticated requests
     *
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $isAuthenticated = (bool)$this->session->get('isAuthenticated', false);
        if (!$isAuthenticated) {
            throw new HttpUnauthorizedException($request);
        }

        return $handler->handle($request);
    }
}
