<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    /**
     * Display payroll data.
     */
    public function index(Request $request)
    {
        // TODO: Implement payroll data retrieval with proper authorization
        return response()->json([
            'message' => 'Payroll endpoint - implementation pending',
        ]);
    }
}
