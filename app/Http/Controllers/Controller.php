<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponser;

/**
 * Base Controller class for the application.
 */
abstract class Controller
{
    use ApiResponser;
}
