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
        return view('customer.booking');
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
