<?php

declare(strict_types=1);

namespace Feldspar\Controllers;

use Feldspar\Traits\AuthenticationState;
use Feldspar\Traits\ParamsFromRequest;
use Feldspar\Traits\RedirectResponse;
use Feldspar\Traits\ValidateData;

abstract class Controller
{
    use AuthenticationState;
    use ParamsFromRequest;
    use RedirectResponse;
    use ValidateData;
}
