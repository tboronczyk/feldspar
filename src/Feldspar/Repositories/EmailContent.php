<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Entities\EmailContent as EmailContentEntity;

class EmailContent
{
    protected const string CONTENT_DIR = 'email';

    /**
     * @param string $templatePath
     */
    public function __construct(
        protected string $templatePath,
    ) {
    }

    /**
     * @param string $key
     * @return ?array
     */
    protected function keyToFiles($key): ?array
    {
        $baseDir = realpath($this->templatePath . '/' . self::CONTENT_DIR);
        assert($baseDir !== false);

        $htmlTarget = realpath($baseDir . '/' . $key . '.html');
        if ($htmlTarget === false || !str_starts_with($htmlTarget, $baseDir)) {
            $htmlTarget = null;
        }

        $txtTarget = realpath($baseDir . '/' . $key . '.txt');
        if ($txtTarget === false || !str_starts_with($txtTarget, $baseDir)) {
            $txtTarget = null;
        }

        if (is_null($txtTarget) && is_null($htmlTarget)) {
            return null;
        }

        return [
            'html' => $htmlTarget,
            'txt' => $txtTarget,
        ];
    }

    /**
     * @param string $key
     * @return ?EmailContentEntity
     */
    public function fetch(string $key): ?EmailContentEntity
    {
        $targets = $this->keyToFiles($key);
        if ($targets === null) {
            return null;
        }

        $htmlContent = null;
        if (!is_null($targets['html'])) {
            $content = file_get_contents($targets['html']);
            if ($content !== false) {
                $htmlContent = $content;
            }
        }

        $txtContent = null;
        if (!is_null($targets['txt'])) {
            $content = file_get_contents($targets['txt']);
            if ($content !== false) {
                $txtContent = $content;
            }
        }

        if (is_null($htmlContent) && is_null($txtContent)) {
            return null;
        }

        return new EmailContentEntity(
            html: $htmlContent ?? '',
            txt: $txtContent ?? '',
        );
    }
}
