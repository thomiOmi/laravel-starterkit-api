<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    public static $wrap = 'data';

    public function with(Request $request): array
    {
        return [
            'status' => 'Success',
            'meta' => [
                'api_version' => '1.0.0',
            ],
        ];
    }
}
