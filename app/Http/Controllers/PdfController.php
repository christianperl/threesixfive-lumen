<?php

namespace App\Http\Controllers;

use App\Services\Pdf;
use App\Traits\MenuTrait;
use Illuminate\Support\Facades\Auth;

class PdfController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function generateWeekPlan($year, $weekNumber)
    {
        $pdf = new Pdf();

        //return response()->json($this->getMenuWeek($year, $week, false, false, false));

        $week = $this->getMenuWeek($year, $weekNumber, false, true, false);

        if (!is_array($week) && $week->getStatusCode() === 404) {
            return response()->json([410 => 'There seems to be something wrong'], 410);
        }

        $pdf->generateWeek($week, $year, $weekNumber);
    }

    public function generateGroceryList()
    {
        $pdf = new Pdf();

        $grocerylist = GroceryListController::getCurrentGroceryList(false);

        if ($grocerylist === []) {
            return response()->json([404 => 'Nothing found. There seems to be something wrong'], 404);
        }

        $pdf->generateGroceryList($grocerylist, Auth::user());
    }
}