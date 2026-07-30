<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Park;

class ParkController extends Controller
{
    public function index()
    {
        return response()->json(Park::orderBy('park_name')->get());
    }
}
