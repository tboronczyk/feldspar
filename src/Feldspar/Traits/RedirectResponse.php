<?php

declare(strict_types=1);

namespace Feldspar\Traits;

use Psr\Http\Message\ResponseInterface as Response;

trait RedirectResponse
{
    /**
     * Return a redirect response
     *
     * @param Response $resp
     * @param string $location
     * @param int $status (optional, default 307)
     * @return Response
     */
    protected function redirectResponse(Response $resp, string $location, int $status = 307): Response
    {
        return $resp->withStatus($status)->withHeader('Location', $location);
    }
}
