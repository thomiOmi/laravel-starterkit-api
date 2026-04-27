<?php

declare(strict_types=1);

namespace Modules\Health\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Check the health of the application and its dependencies.
     */
    public function check(): JsonResponse
    {
        $status = 'ok';
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $status = 'error';
                break;
            }
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'services' => $checks,
        ], $status === 'ok' ? 200 : 503);
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 5);
            if (Cache::get('health_check')) {
                return ['status' => 'ok'];
            }

            return ['status' => 'error', 'message' => 'Cache write/read failed'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $path = storage_path('app/health_check.txt');
            file_put_contents($path, 'ok');
            if (file_get_contents($path) === 'ok') {
                unlink($path);

                return ['status' => 'ok'];
            }

            return ['status' => 'error', 'message' => 'Storage write/read failed'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
