<?php

namespace App\Http\Controllers;

use App\Recipe;
use App\Services\Api;
use App\Traits\MenuTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    use MenuTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getMenuDay($date)
    {
        $dayPlan = null;

        $select = DB::table('plans')
            ->where('pk_fk_user_id', '=', Auth::id())
            ->where('pk_date', '=', $date)
            ->get([
                'weekday',
                'breakfast',
                'lunch',
                'main_dish',
                'snack'
            ]);

        if ($select->isEmpty()) {
            return response()->json([404 => 'Day not generated yet'], 404);
        }

        foreach (['breakfast', 'lunch', 'main_dish', 'snack'] as $type) {
            if (isset($select[0]->$type)) {
                $dayPlan[$type === 'main_dish' ? 'main dish' : $type] = (new Recipe(Api::Recipe($select[0]->$type), true))();
            }

        }return response()->json($dayPlan);
    }
}
