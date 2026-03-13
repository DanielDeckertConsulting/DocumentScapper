<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready(): JsonResponse
    {
        $dbOk = true;
        $storageOk = true;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $dbOk = false;
        }

        try {
            Storage::disk(config('filesystems.default'))->exists('.');
        } catch (\Throwable) {
            $storageOk = false;
        }

        $ready = $dbOk && $storageOk;

        return response()->json([
            'status' => $ready ? 'ready' : 'not_ready',
            'database' => $dbOk ? 'ok' : 'error',
            'storage' => $storageOk ? 'ok' : 'error',
        ], $ready ? 200 : 503);
    }
}
