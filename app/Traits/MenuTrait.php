<?php

namespace App\Traits;

use App\Recipe;
use FatSecret;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\CacheTrait;

trait MenuTrait
{
    use CacheTrait;

    public function getMenuWeek($year, $week, $json = true, $detailed = true)
    {

        $date = Carbon::now();
        $date->setISODate($year, $week);
        $weekPlan = null;

        $firstDate = $date->format('Y-m-d');
        $lastDate = $date->endOfWeek()->format('Y-m-d');

        $select = DB::table('plans')
            ->where('pk_fk_user_id', '=', Auth::id())
            ->where('pk_date', '>=', $firstDate)
            ->where('pk_date', '<=', $lastDate)->get();

        foreach ($select as $item) {
            if ($detailed) {
                $weekPlan[$item->weekday]['breakfast'] = (new Recipe($this->cacheRecipe($item->breakfast)))();
                $weekPlan[$item->weekday]['lunch'] = (new Recipe($this->cacheRecipe($item->lunch)))();
                $weekPlan[$item->weekday]['main_dish'] = (new Recipe($this->cacheRecipe($item->main_dish)))();
                $weekPlan[$item->weekday]['snack'] = (new Recipe($this->cacheRecipe($item->snack)))();
            } else {

            }
        }

        if ($json) {
            return response()->json($weekPlan);
        } else {
            return $weekPlan;
        }
    }
}