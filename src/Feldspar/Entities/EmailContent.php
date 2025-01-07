<?php

declare(strict_types=1);

namespace Feldspar\Entities;

class EmailContent
{
    public function __construct(
        public string $txt = '',
        public string $html = '',
    ) {
    }
}
