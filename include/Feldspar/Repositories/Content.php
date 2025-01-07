<?php

declare(strict_types=1);

namespace Feldspar\Repositories;

class Content
{
    protected const string CONTENT_DIR = __DIR__ . '/../../../templates';
    protected const string CONTENT_EXT = '.html';

    /**
     * Parse meta data
     *
     * @param string $meta
     * @return array
     */
    protected function parseMeta(string $meta): array
    {
        $result = [];

        $lines = explode("\n", $meta);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $result;
    }

    /**
     * Split content into meta and content
     *
     * @param string $content
     * @return array
     */
    protected function split(string $content): array
    {
        $parts = explode('---', $content, 3);
        if (count($parts) == 3) {
            return [
                ... $this->parseMeta($parts[1]),
                'content' => $parts[2]
            ];
        }

        return ['content' => $content];
    }

    /**
     * Retrieve content by identifer
     *
     * @param string $key
     * @return array
     */
    public function get(string $key): array
    {
        $baseDir = realpath(self::CONTENT_DIR);
        assert($baseDir !== false);

        $targetPath = realpath($baseDir . '/' . $key . self::CONTENT_EXT);
        if ($targetPath === false) {
            $targetPath = realpath($baseDir . '/' . $key . '/index' . self::CONTENT_EXT);
        }

        if ($targetPath === false || !str_starts_with($targetPath, $baseDir)) {
            return [];
        }

        $content = file_get_contents($targetPath);
        if ($content === false) {
            return [];
        }

        $result = $this->split($content);
        return $result;
    }
}
