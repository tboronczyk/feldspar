<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Repositories\Content as ContentRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class Page extends Controller
{
    protected ContentRepository $content;

    /**
     * Set content repository
     *
     * @param ContentRepository $repo
     */
    public function setContentRepository(ContentRepository $repo): void
    {
        $this->content = $repo;
    }

    /**
     * Serve the requested page
     *
     * @param Request $req
     * @param Response $resp
     * @param array $args
     * @return Response
     */
    public function get(Request $req, Response $resp, array $args): Response
    {
        $path = ltrim($req->getUri()->getPath(), '/');
        if ($path == '') {
            $path = 'index';
        }

        $page = $this->content->get('content/' . $path);
        if (empty($page)) {
            throw new HttpNotFoundException($req);
        }
        $page['content'] = $this->twig->fetchFromString($page['content']);

        return $this->twig->render($resp, 'layouts/default.html', $page);
    }
}
