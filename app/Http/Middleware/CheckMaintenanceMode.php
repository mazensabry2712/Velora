<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (SystemSetting::get('maintenance_mode', false)) {
            return response()->view('landing.maintenance', [
                'appName' => SystemSetting::get('app_name', config('app.name', 'Velora')),
            ], 503);
        }

        return $next($request);
    }
}
