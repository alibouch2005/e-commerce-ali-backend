<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        // Public registration can only create customers. Admins assign delivery roles.
        $data['role'] = 'client';
        $user = User::create($data);
        Auth::login($user);

        return response()->json(['user' => $user], 201);
    }
}
