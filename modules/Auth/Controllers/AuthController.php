<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LoginAction;
use Modules\User\Resources\UserResource;

class AuthController extends Controller
{
    public function login(Request $request, LoginAction $action)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $action->execute($request->only('email', 'password'));

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 'Login berhasil');
    }

    public function logout(Request $request)
    {
        // Menghapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil');
    }

    public function me(Request $request)
    {
        return $this->successResponse(new UserResource($request->user()), 'Data profile berhasil diambil');
    }
}
