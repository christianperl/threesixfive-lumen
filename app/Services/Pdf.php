<?php

namespace App\Services;

use Carbon\Carbon;
use Elibyy\TCPDF\Facades\TCPDF;

class Pdf
{

    private $pdf;

    public function __construct()
    {
        $this->pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);

        $this->pdf::SetCreator('ThreeSixFive');
        $this->pdf::SetAuthor('ThreeSixFive');
        $this->pdf::SetTitle('Week Plan');
        $this->pdf::SetSubject('Diet plan for the week');
        $this->pdf::SetKeywords('ThreeSixFive, Food, Recipes, Plan, Diet plan');

        $this->pdf::setPrintHeader(false);
        $this->pdf::setPrintFooter(false);

        //$this->pdf::SetFont('Helvetica', 'BI', 10);

        $this->pdf::SetMargins(15, 15, 15, true);
        $this->pdf::SetAutoPageBreak(true, 15);
    }

    public function generate($weekPlan, $year, $weekNumber)
    {
        $date = Carbon::now();
        $date->setISODate($year, $weekNumber);

        $firstDate = $date->format('d.m.Y');
        $lastDate = $date->endOfWeek()->format('d.m.Y');

        $this->pdf::AddPage('L');

        $html = '<div>
    <h1>Week ' . $weekNumber . '/' . $year . '</h1>
    <h4>' . $firstDate . '  -  ' . $lastDate . '</h4>
</div>
<div style="border-top: 2px solid #f8b735"></div>

<table cellspacing="5" cellpadding="5">
    <tr style="color: #213037; font-size: large; font-weight: bold">
        <th style="color: #2b898b">Type</th>
        <th>Monday</th>
        <th>Tuesday</th>
        <th>Wednesday</th>
        <th>Thursday</th>
        <th>Friday</th>
        <th>Saturday</th>
        <th>Sunday</th>
    </tr>';

        $html .= $this->generateOverview($weekPlan);

        $html .= '</table>';

        $this->pdf::writeHTML($html, true, false, false, false, 'C');

        foreach ($weekPlan as $name => $day) {
            if ($day != []) {
                foreach (['breakfast', 'lunch', 'main dish', 'snack'] as $type) {
                    if (isset($day[$type])) {
                        $this->pdf::AddPage('L');

                        $html = '<h1 align="left">' . $name . '</h1>
                        <div style="border-top: 2px solid #f8b735"></div>';

                        $html .= '<h3 align="center" style="font-size: large;"><span style="font-size: small; color: #2b898b">' . ucfirst($type) . '  </span>' . $day[$type]['name'] . '</h3>';

                        $html .= '<p align="left">' . $day[$type]['description'] . '</p>';

                        $html .= '<p align="left" style="color: #2b898b">Ingredients</p><ul>';
                        foreach ($day[$type]['ingredients'] as $ingredient) {
                            $html .= '<li>' . $ingredient['name'] . '<span style="color: #354249; font-size: small;">  ' . $ingredient['unit'] . ' ' . $ingredient['measurement'] . '</span></li>';
                        }

                        $html .= '</ul><p align="left" style="color: #2b898b">Directions</p><ol>';
                        foreach ($day[$type]['directions'] as $direction) {
                            $html .= '<li>  ' . $direction['direction_description'] . '</li>';
                        }

                        $html .= '</ol><div></div>';

                        $this->pdf::writeHTML($html, true, false, false, false, 'C');
                    }
                }
            }
        }

        $this->pdf::Output('Week-' . $weekNumber . '.pdf', 'D');
    }

    private function generateOverview($weekplan)
    {
        $table = '';

        foreach (['breakfast', 'lunch', 'main dish', 'snack'] as $type) {
            $table .= '<tr style="background-color: #ebf4f4;"><td style="background-color: white; color: #2b898b">' . ucfirst($type) . '</td>';

            foreach ($weekplan as $day) {
                if (isset($day[$type])) {
                    $table .= '<td align="center" style="margin: auto">' . $day[$type]['name'] . '</td>';
                } else {
                    $table .= '<td></td>';
                }
            }

            $table .= '</tr>';
        }

        return $table;
    }

}