<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
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

        $token = $this->issueToken($user);

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

        // Determina se vai buscar pelo email ou pelo username
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        } else {
            $user = User::where('username', $data['username'])->first();
        }
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $this->issueToken($user);

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

    private function issueToken(User $user): string
    {
        $secret = config('app.supabase_jwt_secret', env('SUPABASE_JWT_SECRET'));
        $ttl = (int) env('JWT_TTL', 60 * 60 * 24 * 7); // 7 days
        $now = time();

        $payload = [
            'iss' => 'supabase',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'sub' => (string) $user->id,
            'role' => 'anon',
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}
