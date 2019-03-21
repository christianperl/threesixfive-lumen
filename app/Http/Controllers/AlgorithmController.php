<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Algorithm;

class AlgorithmController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function createAlgorithm(Request $request)
    {
        $Algorithm = new Algorithm(
            $request->plan,
            $request->allergens,
            $request->categories,
            $request->diets
        );

        $Algorithm->saveUserPreferences();

        $Week = $Algorithm->generateWeek();

        $date = Carbon::now();
        $Algorithm->saveWeek($Week, $date->year, $date->week);

        return response()->json([201 => 'Week successfully generated'], 201);
    }
}
