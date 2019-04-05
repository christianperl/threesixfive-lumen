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
        $recipe_id = 434219;
        $ids = [];
        $today = Carbon::today()->format('Y-m-d');
        $pastWeek = Carbon::today()->subWeek()->format('Y-m-d');

        $select = DB::table('plans')
            ->where('pk_fk_user_id', '=', Auth::id())
            ->where('pk_date', '<=', $today)
            ->where('pk_date', '>=', $pastWeek)
            ->get([
                'breakfast',
                'lunch',
                'main_dish',
                'snack'
            ]);

        foreach ($select as $item) {
            foreach (['breakfast', 'lunch', 'main_dish', 'snack'] as $type) {
                $ids[] = $item->$type;
            }
        }

        return response()->json(in_array($recipe_id, $ids, true));
    }
}
