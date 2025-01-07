<?php

declare(strict_types=1);

namespace Feldspar\Views;

use Feldspar\Repositories\Content as ContentRepository;
use Slim\Error\Renderers\HtmlErrorRenderer as SlimHtmlErrorRenderer;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig as SlimTwig;
use Throwable;

class HtmlErrorRenderer extends SlimHtmlErrorRenderer
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
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if (!$exception instanceof HttpNotFoundException) {
            return parent::__invoke($exception, $displayErrorDetails);
        }

        $page = $this->contentRepository->fetch('status-404');
        assert(!is_null($page));

        $page->content = $this->twig->fetchFromString($page->content);
        return $this->twig->fetch('layouts/page.html', ['page' => $page]);
    }
}
