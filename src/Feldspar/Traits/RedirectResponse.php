<?php

declare(strict_types=1);

namespace Feldspar\Traits;

use Psr\Http\Message\ResponseInterface as Response;

trait RedirectResponse
{
    /**
     * @param Response $resp
     * @param string $location
     * @param int $status (optional, default 303)
     * @return Response
     */
    protected function redirectResponse(Response $resp, string $location, int $status = 303): Response
    {
        return $resp->withStatus($status)->withHeader('Location', $location);
    }
}
