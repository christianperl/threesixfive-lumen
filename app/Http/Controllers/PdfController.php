<?php

namespace App\Http\Controllers;

use App\Services\Pdf;
use App\Traits\MenuTrait;

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
            return response()->json([410 => 'There must be something wrong'], 410);
        }

        $pdf->generate($week, $year, $weekNumber);
    }
}