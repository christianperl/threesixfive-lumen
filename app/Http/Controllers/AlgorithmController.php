<?php

namespace App\Http\Controllers;

use App\Jobs\Recache;
use App\Recipe;
use App\Services\Api;
use Carbon\Carbon;
use Illuminate\Http\Request;
use FatSecret;
use App\Services\Algorithm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Util\Json;
use Predis\Client;

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
        $Week = $Algorithm->saveWeek($Week, 0);

        return response()->json($Week);
    }

    public function generateWeek($weekNumber)
    {
        /*$Algorithm = new Algorithm(

        );

        $Week = $Algorithm->generateWeek();
        $Week = $Algorithm->saveWeek($Week, $week);

        return response()->json($Week);*/
        //$test = new Client();

        //$key = 'lumen_cache:' . 39068;

        //$test = new Recache(39068, 'r');

        //$id = Auth::id();

        //$a = Api::Ingredient(40645);
        //$a = Api::Recipe(141);

        //return response()->json($this->cacheRecipe(30980));
        return response()->json((new Recipe(Api::Recipe(141)))());

    }
}
