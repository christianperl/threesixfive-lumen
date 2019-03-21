<?php

namespace App\Traits;

use App\Recipe;
use App\Services\Algorithm;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Api;

trait MenuTrait
{
    public function getMenuWeek($year, $week, $json = true, $detailed = false, $generateIfNotExisting = true)
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

        if (!$select->isEmpty()) {
            foreach ($select as $item) {
                $weekPlan[$item->weekday] = [];
                foreach (['breakfast', 'lunch', 'main_dish', 'snack'] as $type) {
                    if ($item->$type !== null) {
                        $weekPlan[$item->weekday][$type === 'main_dish' ? 'main dish' : $type] = (new Recipe(Api::Recipe($item->$type), $detailed))();
                    }
                }
            }

            if ($json) {
                return response()->json($weekPlan);
            } else {
                return $weekPlan;
            }
        } else {
            if (Carbon::now()->isAfter($firstDate)) {
                return response()->json([404 => 'There is no plan for this week available'], 404);
            } else {
                $Algorithm = new Algorithm();

                $Week = $Algorithm->generateWeek();
                $Week = $Algorithm->saveWeek($Week, $year, $week);

                return $Week;
            }
        }

    }
}