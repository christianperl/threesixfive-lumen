<?php

namespace App\Http\Controllers;

use App\Recipe;
use App\Traits\CacheTrait;
use App\Traits\MenuTrait;
use FatSecret;
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
            ->where('pk_date', '=', $date)->first();

        if ($select != []) {
            $dayPlan[$select->weekday]['breakfast'] = (new Recipe($this->cacheRecipe($select->breakfast)))();
            $dayPlan[$select->weekday]['lunch'] = (new Recipe($this->cacheRecipe($select->lunch)))();
            $dayPlan[$select->weekday]['main_dish'] = (new Recipe($this->cacheRecipe($select->main_dish)))();
            $dayPlan[$select->weekday]['snack'] = (new Recipe($this->cacheRecipe($select->snack)))();
        }

        return response()->json($dayPlan);
    }
}
