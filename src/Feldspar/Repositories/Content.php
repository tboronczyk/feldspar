<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

use Feldspar\Entities\Content as ContentEntity;

class Content
{
    protected const string CONTENT_DIR = 'content';
    protected const string CONTENT_EXT = '.html';

    /**
     * @param string $templatePath
     */
    public function __construct(
        protected string $templatePath,
    ) {
    }

    /**
     * @param string $key
     * @return ?string
     */
    protected function keyToFile($key): ?string
    {
        $baseDir = realpath($this->templatePath . '/' . self::CONTENT_DIR);
        assert($baseDir !== false);

        $target = realpath($baseDir . '/' . $key . self::CONTENT_EXT);
        if ($target === false) {
            $key = str_replace('/', '-', $key);
            $target = realpath($baseDir . '/' . $key . self::CONTENT_EXT);
        }

        if ($target === false || !str_starts_with($target, $baseDir)) {
            return null;
        }

        return $target;
    }

    /**
     * @param string $content
     * return ContentEntity
     */
    protected function parseContent(string $content): ContentEntity
    {
        $entity = new ContentEntity();

        $parts = explode('---', $content, 3);
        if (count($parts) === 3) {
            $meta = [];
            $lines = explode("\n", $parts[1]);
            foreach ($lines as $line) {
                $metaParts = explode(':', $line, 2);
                if (count($metaParts) === 2) {
                    $meta[trim($metaParts[0])] = trim($metaParts[1]);
                }
            }

            $entity->title = (isset($meta['title'])) ? $meta['title'] : '';
            $entity->description = (isset($meta['description'])) ? $meta['description'] : '';
            $entity->content = $parts[2];
            return $entity;
        }

        $entity->content = $content;
        return $entity;
    }

    /**
     * @param string $key
     * @return ?ContentEntity
     */
    public function fetch(string $key): ?ContentEntity
    {
        $target = $this->keyToFile($key);
        if ($target === null) {
            return null;
        }

        $content = file_get_contents($target);
        if ($content === false) {
            return null;
        }

        $result = $this->parseContent($content);
        return $result;
    }
}
