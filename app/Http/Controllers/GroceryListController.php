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

        // Get personal groceries
        if (!($groceries_users = Grocery::where([['fk_user_id', Auth::id()], ['generated', false]])->get(['pk_grocery_id', 'name', 'serving', 'measurement', 'checked', 'generated']))->isEmpty()) {
            foreach ($groceries_users as $grocery) {
                $groceries[] = $grocery;
            }
        }

        DB::table('groceries')->where('generated', true)->delete();

        // Get generated groceries
        $today = Carbon::now()->format('Y-m-d');
        $sunday = Carbon::parse('next sunday')->format('Y-m-d');

        if (Grocery::where([['fk_user_id', Auth::id()], ['generated', true]])->get(['name', 'serving', 'measurement', 'checked', 'generated'])->isEmpty()) {
            if (!($plan = DB::table('plans')->where('pk_fk_user_id', Auth::id())->whereBetween('pk_date', [$today, $sunday])->get(['pk_date', 'breakfast', 'lunch', 'main_dish', 'snack']))->isEmpty()) {
                $recipes = $this->planToGroceries($plan);

                foreach ($recipes as $recipe) {
                    foreach ($recipe[0]->getIngredients() as $ingredient) {
                        $generated = [
                            'name' => $ingredient->getName(),
                            'serving' => $ingredient->getGroceryUnit(),
                            'measurement' => $ingredient->getGroceryMeasurement(),
                            'checked' => false,
                            'generated' => true
                        ];
                        $groceries[] = $generated;

                        $generated['fk_user_id'] = Auth::id();
                        $generated['day'] = $recipe[1];

                        //Grocery::create($generated);
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
                    'name',
                    'day',
                    'serving',
                    'measurement',
                    'checked',
                    'generated'
                ]);

            foreach ($groceries_from_current_plan as $recipe) {
                foreach ($recipe[0]->getIngredients() as $ingredient) {
                    $new = true;
                    foreach ($current_groceries->toArray() as $item) {
                        if ($item->day === $recipe[1] && $item->name === $ingredient->getName()) {
                            /*$groceries[] = [
                                'name' => $item->name,
                                'serving' => $item->serving,
                                'measurement' => $item->measurement,
                                'checked' => $item->checked,
                                'generated' => $item->generated
                            ];*/
                            $current_groceries->forget($current_groceries->search((object)$item));
                            $new = false;
                            break;
                        }
                    }

                    if (false) {
                        Grocery::create([
                            'name' => $ingredient->getName(),
                            'serving' => $ingredient->getGroceryUnit(),
                            'measurement' => $ingredient->getGroceryMeasurement(),
                            'checked' => false,
                            'generated' => true,
                            'fk_user_id' => Auth::id(),
                            'day' => $recipe[1]
                        ]);
                    }
                }
            }

            foreach ($current_groceries as $old_grocery) {
                DB::table('groceries')
                    ->where([
                        ['fk_user_id', Auth::id()],
                        ['day', $old_grocery->day],
                        ['pk_grocery_id', $old_grocery->pk_grocery_id]
                    ])
                    ->delete();
            }
        }

        return response()->json($groceries);
    }

    public function getNextGroceryList()
    {
        $nextMonday = (new Carbon('next Monday'))->format('Y-m-d');
        $nextSunday = (new Carbon('next Monday'))->endOfWeek()->format('Y-m-d');
        $nextGroceries = [];

        if (!($nextWeek = Plan::where('pk_fk_user_id', '=', Auth::id())->whereBetween('pk_date', [$nextMonday, $nextSunday])->get(['pk_date', 'breakfast', 'lunch', 'main_dish', 'snack']))->isEmpty()) {

            $recipes = $this->planToGroceries($nextWeek);

            foreach ($recipes as $recipe) {
                foreach ($recipe[0]->getIngredients() as $ingredient) {
                    $nextGroceries[] = [
                        'name' => $ingredient->getName(),
                        'serving' => $ingredient->getGroceryUnit(),
                        'measurement' => $ingredient->getGroceryMeasurement()
                    ];
                }
            }

            return $nextGroceries;
        }

        return response()->json(['The plan for the next week is not generated yet.' => 428], 428);
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

    public function deleteGrocery()
    {

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
