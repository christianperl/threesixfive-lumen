<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Algorithm;
use Illuminate\Support\Facades\DB;

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

        return response()->json([201 => 'User profile successfully created'], 201);
    }

    public function regenerateType(Request $request)
    {
        $type = $request->get('type') === 'main dish' ? 'main_dish' : $request->get('type');

        $recipe = DB::table('plans')
            ->where([
                ['pk_date', $request->get('date')],
                [$type, '!=', null]
            ]);

        if ($recipe->get()->isEmpty()) {
            return response()->json([404 => 'Recipe not found'], 404);
        }

        $Algorithm = new Algorithm();

        $recipe->update([$type => $Algorithm->generateOneType($request->get('type'))]);

        return response()->json([201 => 'Recipe successfully regenerated'], 201);
    }
}
