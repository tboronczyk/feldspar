<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Repositories\Content as ContentRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig as SlimTwig;

class Page
{
    /**
     * @param SlimTwig $twig
     * @param ContentRepository $contentRepository
     */
    public function __construct(
        protected SlimTwig $twig,
        protected ContentRepository $contentRepository,
    ) {
    }

    /**
     * @param Request $req
     * @param Response $resp
     * @param array<string,string> $args
     * @return Response
     */
    public function get(Request $req, Response $resp, array $args): Response
    {
        $path = ltrim($req->getUri()->getPath(), '/');
        if ($path === '') {
            $path = 'index';
        }

        $page = $this->contentRepository->fetch($path);
        if (is_null($page)) {
            throw new HttpNotFoundException($req);
        }

        $page->content = $this->twig->fetchFromString($page->content);

        return $this->twig->render($resp, 'layouts/page.html', ['page' => $page]);
    }
}
