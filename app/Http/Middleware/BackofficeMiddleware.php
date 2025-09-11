<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BackofficeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->type !== 'admin') {
            // Fazer logout do usuário
            Auth::logout();
            
            // Invalidar a sessão
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Redirecionar para login com mensagem de erro
            return redirect()->route('login')
                ->withErrors(['access' => 'Acesso negado. Apenas administradores podem acessar o backoffice. Faça login com uma conta de administrador.']);
        }

        return $next($request);
    }
}