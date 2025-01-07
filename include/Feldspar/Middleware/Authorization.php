<?php

declare(strict_types=1);

namespace Feldspar\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Odan\Session\PhpSession;
use Slim\Exception\HttpUnauthorizedException;

class Authorization implements MiddlewareInterface
{
    protected PhpSession $session;

    /**
     * Set session
     *
     * @param PhpSession $session
     */
    public function setSession(PhpSession $session): void
    {
        $this->session = $session;
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
