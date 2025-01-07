<?php

declare(strict_types=1);

namespace Feldspar\Views;

use Feldspar\Repositories\Content as ContentRepository;
use Slim\Error\Renderers\HtmlErrorRenderer as SlimHtmlErrorRenderer;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Views\Twig as SlimTwig;
use Throwable;

class HtmlErrorRenderer extends SlimHtmlErrorRenderer
{
    protected SlimTwig $twig;
    protected ContentRepository $content;

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
     * Set content repository
     *
     * @param ContentRepository $repo
     */
    public function setContentRepository(ContentRepository $repo): void
    {
        $this->content = $repo;
    }

    /**
     * Return rendered HTML error page
     *
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if (
            !$exception instanceof HttpNotFoundException
            && !$exception instanceof HttpUnauthorizedException
        ) {
            return parent::__invoke($exception, $displayErrorDetails);
        }

        $page = $this->content->get('status/' . (string)$exception->getCode());
        $page['content'] = $this->twig->fetchFromString($page['content']);
        return $this->twig->fetch('layouts/default.html', $page);
    }
}
