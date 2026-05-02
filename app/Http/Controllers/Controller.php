<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base Controller class for the application.
 */
abstract class Controller extends BaseController
{
    use ApiResponser;
}
