<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Algorithm;
use Illuminate\Support\Facades\Auth;
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
            $request->get('plan'),
            $request->get('allergens'),
            $request->get('categories'),
            $request->get('diets'),
            $request->get('persons')
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
                ['pk_fk_user_id', Auth::id()],
                [$type, '!=', null]
            ]);

        if ($recipe->get()->isEmpty()) {
            return response()->json([404 => 'Recipe not found'], 404);
        }

        $Algorithm = new Algorithm();

        $recipe->update([$type => $Algorithm->generateOneType($request->get('type'))]);

        return response()->json([201 => 'Recipe successfully regenerated'], 201);
    }

    public function getUserPreferences()
    {
        $plan = DB::table('user_days')
            ->where('pk_fk_user_id', Auth::id())
            ->get([
                'weekday',
                'breakfast',
                'lunch',
                'main_dish',
                'snack',
            ]);

        $diets = DB::table('user_diets')
            ->join('diets', 'pk_fk_u_diets_id', '=', 'pk_diet_id')
            ->where('pk_fk_d_user_id', Auth::id())
            ->get([
                'description'
            ]);

        $categories = DB::table('nogos')
            ->join('categories', 'fk_object', '=', 'pk_category_id')
            ->where([
                ['fk_n_user_id', Auth::id()],
                ['which', 'category']
            ])
            ->get([
                'name'
            ]);

        $allergens = DB::table('nogos')
            ->join('allergens', 'fk_object', '=', 'pk_allergen_id')
            ->where([
                ['fk_n_user_id', Auth::id()],
                ['which', 'allergen']
            ])
            ->get([
                'description'
            ]);

        $persons = DB::table('users')
            ->where('pk_user_id', Auth::id())
            ->value('persons');

        $preferences = [];

        foreach ([['persons', $persons], ['diets', $diets], ['categories', $categories], ['allergens', $allergens], ['plan', $plan]] as $value) {
            $preferences[$value[0]] = $value[1];
        }

        return response()->json($preferences);
    }

    public function changeUserPreferences(Request $request)
    {
        DB::table('user_diets')->where('pk_fk_d_user_id', Auth::id())->delete();
        DB::table('nogos')->where('fk_n_user_id', Auth::id())->delete();
        DB::table('user_days')->where('pk_fk_user_id', Auth::id())->delete();
        DB::table('users')->where('pk_user_id', Auth::id())->update(['persons' => null]);

        DB::table('plans')
            ->where([
                ['pk_fk_user_id', Auth::id()],
                ['pk_date', '>', Carbon::now()->endOfWeek()->format('Y-m-d')]
            ])
            ->delete();

        $Algorithm = new Algorithm(
            $request->get('plan'),
            $request->get('allergens'),
            $request->get('categories'),
            $request->get('diets'),
            $request->get('persons')
        );

        $Algorithm->saveUserPreferences();
    }
}
