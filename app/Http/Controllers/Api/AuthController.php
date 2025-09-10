<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'username'   => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'   => ['required', 'string', 'min:6'],
            'full_name'  => ['nullable', 'string', 'max:255'],
            'cpf'        => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'type'       => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'username'   => $data['username'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'full_name'  => $data['full_name'] ?? null,
            'cpf'        => $data['cpf'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'type'       => $data['type'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'password' => ['required', 'string'],
            'email'    => ['required_without:username', 'email'],
            'username' => ['required_without:email', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                return response()->json(['message' => 'Email not found'], 404);
            }
        } else {
            $user = User::where('username', $data['username'])->first();
            if (!$user) {
                return response()->json(['message' => 'Username not found'], 404);
            }
        }
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Incorrect password'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $userData = $user->toArray();
        $userData['stats'] = [
            'requests_made' => $user->serviceRequests()->count(),
            'completed' => $user->serviceRequests()->completed()->count(),
            'in_progress' => $user->serviceRequests()->inProgress()->count(),
            'cancelled' => $user->serviceRequests()->cancelled()->count(),
        ];

        return response()->json([
            'user' => $userData
        ]);
    }


}
