<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig as SlimTwig;

class Controller
{
    protected SlimTwig $twig;

    /**
     * Set Twig view renderer
     *
     * @param SlimTwig $twig
     */
    public function setTwig(SlimTwig $twig): void
    {
        $this->twig = $twig;
    }

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

    /**
     * Render a template
     *
     * @param Response $resp
     * @param string $template
     * @param array $data
     * @return Response
     */
    protected function render(Response $resp, string $template, array $data): Response
    {
        return $this->twig->render($resp, $template, $data);
    }
}
