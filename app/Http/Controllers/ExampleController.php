<?php

namespace App\Http\Controllers;

use App\Services\Algorithm;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function test($type) {
        $Algorithm = new Algorithm();

        $recipe = $Algorithm->generateOneType($type);

        return response()->json($recipe);
    }
}
