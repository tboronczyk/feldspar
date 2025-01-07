<?php

declare(strict_types=1);

namespace Feldspar\Traits;

use Psr\Http\Message\ServerRequestInterface as Request;

trait ParamsFromRequest
{
    /**
     * Retrieve parameters from the request
     *
     * @param Request $req
     * @param list<string> $names
     * @return array<string, string>
     */
    protected function paramsFromRequest(Request $req, array $names): array
    {
        /** @var array<string, string> $params */
        $params = (array)$req->getParsedBody();

        $result = [];
        foreach ($names as $n) {
            $result[$n] = trim($params[$n] ?? '');
        }

        return $result;
    }
}
