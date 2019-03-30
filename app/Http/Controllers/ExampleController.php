<?php

namespace App\Http\Controllers;

use App\Plan;
use App\Recipe;
use App\Services\Algorithm;
use App\Services\Api;
use App\User;
use App\UserDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function test(Request $request) {
        $groceries = [];
        $current_groceries = DB::table('groceries')
            ->where('generated', true)
            ->get([
                'day',
                'name'
            ]);

        $today = Carbon::now()->format('Y-m-d');
        $sunday = Carbon::parse('next sunday')->format('Y-m-d');

        $current_plan = DB::table('plans')
            ->where('pk_fk_user_id', Auth::id())
            ->whereBetween('pk_date', [$today, $sunday])
            ->get([
                'pk_date',
                'breakfast',
                'lunch',
                'main_dish',
                'snack'
            ]);
        $groceries_from_current_plan = $this->planToGroceries($current_plan);

        foreach ($groceries_from_current_plan as $recipe) {
            foreach ($recipe[0]->getIngredients() as $ingredient) {

            }
        }

        return response()->json($current_groceries);
    }

    private function planToGroceries($plan)
    {
        $recipes = [];
        foreach ($plan as $day) {
            foreach (['breakfast', 'lunch', 'main_dish', 'snack'] as $type) {
                if ($day->$type !== null) {
                    $recipes[] = [new Recipe(Api::Recipe($day->$type), true), $day->pk_date];
                }
            }
        }

        return $recipes;
    }
}
