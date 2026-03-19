<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
{
    if ($request->has('role')) {
        return User::where('role', $request->role)->get();
    }

    return User::all();
}
}
