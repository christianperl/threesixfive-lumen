<?php

namespace App\Http\Controllers;

use App\Grocery;
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
        $this->middleware('auth');
    }

    public function test(Request $request)
    {
        $groceries = [];
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
                $new = true;
                foreach ($current_groceries->toArray() as $item) {
                    if ($item->day === $recipe[1] && $item->name === $ingredient->getName()) {
                        $groceries[] = [
                            'name' => $item->name,
                            'serving' => $item->serving,
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
                    ['pk_groceries_id', $old_grocery->pk_groceries_id]
                ])
                ->delete();
        }

        return response()->json($groceries);
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
