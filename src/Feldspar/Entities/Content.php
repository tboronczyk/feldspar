<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class Content
{
    public function __construct(
        public string $title = '',
        public string $description = '',
        public string $content = '',
    ) {
    }
}
