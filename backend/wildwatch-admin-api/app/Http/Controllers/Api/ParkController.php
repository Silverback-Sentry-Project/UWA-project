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

    // Unauthenticated — the sign-in screen needs the park list before login
    // to populate the Gamepark dropdown. Only id/name are exposed here.
    public function publicIndex()
    {
        return response()->json(
            Park::orderBy('park_name')->get(['park_id', 'park_name'])
        );
    }
}
