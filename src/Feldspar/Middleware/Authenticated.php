<?php

declare(strict_types=1);

namespace Feldspar\Middleware;

use Feldspar\Traits\RedirectResponse;
use Odan\Session\SessionInterface as Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\Factory\DecoratedResponseFactory;

class Authenticated implements MiddlewareInterface
{
    use RedirectResponse;

    /**
     * @param Session $session
     */
    public function __construct(
        protected Session $session,
        protected DecoratedResponseFactory $responseFactory
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
        $uri = $request->getUri()->getPath();
        $isAuthenticated = (bool)$this->session->get('isAuthenticated', false);

        if ($uri !== '/ensaluti' && !$isAuthenticated) {
            $resp = $this->responseFactory->createResponse();
            return $this->redirectResponse($resp, '/ensaluti');
        }

        return $handler->handle($request);
    }
}
