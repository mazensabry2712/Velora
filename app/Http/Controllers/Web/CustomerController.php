<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Show booking page
     */
    public function booking()
    {
        // Get available languages from settings
        $settingsModel = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $availableLanguages = $settingsModel && $settingsModel->available_languages
            ? $settingsModel->available_languages
            : ['en', 'ar'];

        // Ensure it's an array
        if (!is_array($availableLanguages)) {
            $availableLanguages = ['en', 'ar'];
        }

        return view('customer.booking', compact('availableLanguages'));
    }

    /**
     * Check my queue status
     */
    public function myQueue(Request $request)
    {
        $queueNumber = $request->input('queue_number');
        return view('customer.my-queue', compact('queueNumber'));
    }
}
