<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);
        $secret = config('app.supabase_jwt_secret', env('SUPABASE_JWT_SECRET'));
        if (!$secret) {
            return response()->json(['message' => 'JWT secret not configured'], 500);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Exception $e) {
            return response()->json(['message' => 'Invalid token', 'error' => $e->getMessage()], 401);
        }

        // Expect user id in the 'sub' claim
        $userId = $decoded->sub ?? null;
        if (!$userId) {
            return response()->json(['message' => 'Token missing subject'], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        // Attach user resolver and token payload
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('token_payload', (array) $decoded);

        return $next($request);
    }
}
