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
    /**
     * Constructor
     *
     * @param SlimTwig $twig
     * @param ContentRepository $content
     */
    public function __construct(
        protected SlimTwig $twig,
        protected ContentRepository $content,
    ) {
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

        $page = $this->content->fetch('status-' . (string)$exception->getCode());
        assert($page !== null);

        $page->content = $this->twig->fetchFromString($page->content);
        return $this->twig->fetch('layouts/default.html', ['page' => $page]);
    }
}
