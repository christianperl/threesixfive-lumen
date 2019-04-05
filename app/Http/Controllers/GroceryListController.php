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

    public static function getCurrentGroceryList($json = true)
    {
        $groceries = [];

        // Get personal groceries
        if (!($groceries_users = Grocery::where([['fk_user_id', Auth::id()], ['generated', false]])->get(['pk_grocery_id', 'name', 'serving', 'measurement', 'checked', 'generated']))->isEmpty()) {
            foreach ($groceries_users as $grocery) {
                $grocery['grocery_id'] = $grocery['pk_grocery_id'];
                unset($grocery['pk_grocery_id']);
                $groceries[] = $grocery;
            }
        }

        // Get generated groceries
        $today = Carbon::now()->format('Y-m-d');
        $sunday = Carbon::parse('next sunday')->format('Y-m-d');

        if (Grocery::where([['fk_user_id', Auth::id()], ['generated', true]])->get(['name', 'serving', 'measurement', 'checked', 'generated'])->isEmpty()) {
            if (!($plan = DB::table('plans')->where('pk_fk_user_id', Auth::id())->whereBetween('pk_date', [$today, $sunday])->get(['pk_date', 'breakfast', 'lunch', 'main_dish', 'snack']))->isEmpty()) {
                $recipes = self::planToGroceries($plan);

                foreach ($recipes as $recipe) {
                    foreach ($recipe[0]->getIngredients() as $ingredient) {
                        $generated = [
                            'name' => $ingredient->getName(),
                            'serving' => round($ingredient->getGroceryUnit(), 2),
                            'measurement' => $ingredient->getGroceryMeasurement(),
                            'checked' => false,
                            'generated' => true
                        ];
                        $groceries[] = $generated;

                        $generated['fk_user_id'] = Auth::id();
                        $generated['day'] = $recipe[1];

                        Grocery::create($generated);
                    }
                }

                return self::getCurrentGroceryList($json);
            } else {
                return $groceries;
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

            $groceries_from_current_plan = self::planToGroceries($current_plan);

            $current_groceries = DB::table('groceries')
                ->where([
                    ['generated', true],
                    ['fk_user_id', Auth::id()]
                ])
                ->get([
                    'pk_grocery_id',
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
                            $groceries[] = [
                                'grocery_id' => $item->pk_grocery_id,
                                'name' => $item->name,
                                'serving' => round($item->serving, 2),
                                'measurement' => $item->measurement,
                                'checked' => $item->checked,
                                'generated' => $item->generated
                            ];
                            $current_groceries->forget($current_groceries->search((object)$item));
                            $new = false;
                            break;
                        }
                    }

                    if ($new) {
                        $new_grocery = [
                            'name' => $ingredient->getName(),
                            'serving' => $ingredient->getGroceryUnit(),
                            'measurement' => $ingredient->getGroceryMeasurement(),
                            'checked' => false,
                            'generated' => true,
                            'fk_user_id' => Auth::id(),
                            'day' => $recipe[1]
                        ];
                        Grocery::create($new_grocery);

                        $new_grocery['pk_grocery_id'] = DB::table('groceries')->latest('pk_grocery_id')->value('pk_grocery_id');
                        $groceries[] = $new_grocery;
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

        if ($json) {
            return response()->json($groceries);
        }

        return $groceries;
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
                        'serving' => round($ingredient->getGroceryUnit(), 2),
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

    public function deleteGrocery(Request $request)
    {
        Grocery::findOrFail($request->get('grocery_id'))->delete();
        return response()->json([200 => 'Deleted Successfully'], 200);
    }

    public function updateGrocery(Request $request)
    {
        $grocery = Grocery::findOrFail($request->get('grocery_id'));

        $grocery->update($request->except('grocery_id'));
        return response()->json($grocery, 200);
    }

    private static function planToGroceries($plan)
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
