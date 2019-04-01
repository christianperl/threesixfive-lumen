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
        return response()->json(Grocery::where([['fk_user_id', Auth::id()], ['generated', true]])->get(['name', 'serving', 'measurement', 'checked', 'generated'])->isEmpty());
    }
}
