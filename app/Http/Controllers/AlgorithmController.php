<?php

namespace App\Http\Controllers;

use App\Allergen;
use App\Traits\CacheTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use FatSecret;
use App\Services\Algorithm;
use App\Plan;
use Illuminate\Support\Facades\Cache;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AlgorithmController extends Controller
{
    use CacheTrait;

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
        $Week = $Algorithm->saveWeek($Week, 0);

        return response()->json($Week);
    }

    public function generateWeek($week)
    {
        /*$Algorithm = new Algorithm(

        );

        $Week = $Algorithm->generateWeek();
        $Week = $Algorithm->saveWeek($Week, $week);

        return response()->json($Week);*/

        //return response()->json($this->cacheRecipe(30980));
        return response()->json(Cache::has(41389));

    }
}
