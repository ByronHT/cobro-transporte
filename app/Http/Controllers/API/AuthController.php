<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function loginWithCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Código inválido',
                'details' => $validator->errors()
            ], 400);
        }

        $user = User::where('login_code', $request->code)
                    ->where('active', true)
                    ->first();

        if (!$user) {
            return response()->json([
                'error' => 'Código inválido o usuario inactivo'
            ], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'role' => $user->role,
            'token' => $token,
            'message' => 'Login exitoso'
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)
                    ->where('active', true)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Credenciales inválidas'
            ], 401);
        }

        $token = $user->createToken('web-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'role' => $user->role,
            'token' => $token,
            'message' => 'Login exitoso'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
