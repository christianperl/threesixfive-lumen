<?php

namespace App\Http\Controllers;

use App\Grocery;
use App\Plan;
use App\Recipe;
use App\Services\Api;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroceryListController extends Controller
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

    public function getCurrentGroceryList()
    {
        $groceries = [];

        /*if (($groceries_users = Grocery::where('fk_user_id', '=', Auth::id())->get(['name', 'serving', 'measurement', 'checked', 'generated']))->isEmpty()) {
            if ($groceries_users->where('generated', true)->isEmpty()) {
                if (!($plan = Plan::where('pk_fk_user_id', '=', Auth::id())->whereBetween('pk_date', [$today, $sunday])->get(['breakfast', 'lunch', 'main_dish', 'snack']))->isEmpty()) {
                    foreach ($plan as $item) {
                        foreach (['breakfast', 'lunch', 'main_dish', 'snack'] as $type) {
                            if (($recipe_id = $item[$type]) !== null) {
                                $recipe = new Recipe(Api::Recipe($recipe_id));
                                foreach ($recipe->getIngredients() as $ingredient) {
                                    if (Grocery::where('name', $ingredient->getName())->get()->isEmpty()) {
                                        $input = [
                                            'name' => $ingredient->getName(),
                                            'serving' => (($measurement = $ingredient->getMeasurement()) !== 'g' ? $ingredient->getGrams($measurement) : $measurement) * $ingredient->getUnits(),
                                            'measurement' => 'g',
                                            'checked' => false,
                                            'generated' => true
                                        ];

                                        $groceries_users->push($input);

                                        $input['fk_user_id'] = Auth::id();
                                        Grocery::create($input);
                                    } else {
                                        $grams = $ingredient->getGrams($ingredient->getMeasurement());
                                        $currentGrams = Grocery::where('name', $ingredient->getName())->value('serving');

                                        Grocery::where('name', $ingredient->getName())->update(['serving' => $currentGrams + $grams]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }*/

        // Get personal groceries
        if (!($groceries_users = Grocery::where([['fk_user_id', Auth::id()], ['generated', false]])->get(['name', 'serving', 'measurement', 'checked', 'generated']))->isEmpty()) {
            foreach ($groceries_users as $grocery) {
                $groceries[] = $grocery;
            }
        }

        DB::table('groceries')->where('generated', true)->delete();

        // Get generated groceries
        $today = Carbon::now()->format('Y-m-d');
        $sunday = Carbon::parse('next sunday')->format('Y-m-d');

        if (($groceries_generated = Grocery::where([['fk_user_id', Auth::id()], ['generated', true]])->get(['name', 'serving', 'measurement', 'checked', 'generated']))->isEmpty()) {
            if (!($plan = DB::table('plans')->where('pk_fk_user_id', Auth::id())->whereBetween('pk_date', [$today, $sunday])->get(['pk_date', 'breakfast', 'lunch', 'main_dish', 'snack']))->isEmpty()) {
                $recipes = $this->planToGroceries($plan);

                foreach ($recipes as $recipe) {
                    foreach ($recipe[0]->getIngredients() as $ingredient) {
                        $genereated = [
                            'name' => $ingredient->getName(),
                            'serving' => $ingredient->getGroceryUnit(),
                            'measurement' => $ingredient->getGroceryMeasurement(),
                            'checked' => false,
                            'generated' => true
                        ];
                        $groceries[] = $genereated;

                        $genereated['fk_user_id'] = Auth::id();
                        $genereated['day'] = $recipe[1];

                        Grocery::create($genereated);
                    }
                }
            } else {
                return response()->json([404 => 'Week not generated yet'], 404);
            }
        } else {
            Grocery::where('day', '<', $today)->delete();

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

            $current_groceries = DB::table('groceries')
                ->where('generated', true)
                ->get([
                    'day',
                    'name'
                ]);

            foreach ($groceries_from_current_plan as $recipe) {
                foreach ($recipe[0]->getIngredients() as $ingredient) {

                }
            }
        }

        return response()->json($groceries);
    }

    public function getNextGroceryList()
    {
        $nextMonday = (new Carbon('next Monday'))->format('Y-m-d');
        $nextSunday = (new Carbon('next Monday'))->endOfWeek()->format('Y-m-d');

        if (!($nextWeek = Plan::where('pk_fk_user_id', '=', Auth::id())->whereBetween('pk_date', [$nextMonday, $nextSunday])->get(['breakfast', 'lunch', 'main_dish', 'snack']))->isEmpty()) {
            return response()->json($nextWeek);
        } else {
            return response()->json(['The plan for the next week is not generated yet.' => 428], 428);
        }
    }

    public function createIndividualGroceryList(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'serving' => 'required',
            'measurement' => 'required',
            'checked' => 'required'
        ]);

        $input = $request->all();

        $input['fk_user_id'] = Auth::id();
        $input['generated'] = false;

        $grocery = Grocery::create($input);

        return response()->json([201 => 'Grocery added'], 201);
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
