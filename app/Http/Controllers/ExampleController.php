<?php

namespace App\Http\Controllers;

use App\Services\Algorithm;
use App\User;
use App\UserDay;
use Illuminate\Http\Request;

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

    public function test(Request $request) {
        return response()->json(UserDay::where('pk_fk_user_id', User::where('email', '=', $request['email'])->value('pk_user_id'))->get()->isEmpty());
    }
}
