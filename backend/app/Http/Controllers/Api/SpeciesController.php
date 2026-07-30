<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Species;

class SpeciesController extends Controller
{
    public function index()
    {
        return response()->json(Species::orderBy('common_name')->get());
    }
}
