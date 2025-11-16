<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bus;

class BusController extends Controller
{
    /**
     * Display a listing of the buses.
     */
    public function index(Request $request)
    {
        // For now, return all buses.
        // Later, we can add logic to filter for available buses.
        $buses = Bus::with('ruta')->get();
        return response()->json($buses);
    }
}
