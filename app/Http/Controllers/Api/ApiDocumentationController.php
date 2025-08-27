<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class ApiDocumentationController extends Controller
{
    /**
     * Lista todos os endpoints da API disponíveis
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            // Filtra apenas rotas da API (aquelas que começam com 'api/')
            return str_starts_with($route->uri(), 'api/');
        })->map(function ($route) {
            $methods = array_filter($route->methods(), function ($method) {
                return $method !== 'HEAD';
            });

            return [
                'uri' => '/' . $route->uri(),
                'methods' => $methods,
                'parameters' => $this->getAllParameters($route, $methods),
            ];
        })->values();

        return response()->json([
            'message' => 'API Soluções Urbanas - Endpoints disponíveis',
            'total_endpoints' => $routes->count(),
            'base_url' => config('app.url') . '/api',
            'endpoints' => $routes,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Obtém todos os parâmetros (rota + corpo da requisição)
     *
     * @param \Illuminate\Routing\Route $route
     * @param array $methods
     * @return array
     */
    private function getAllParameters($route, array $methods): array
    {
        $parameters = [];
        
        // Obtém parâmetros da rota (parâmetros da URL como {id})
        $routeParameters = $this->getRouteParameters($route);
        $parameters = array_merge($parameters, $routeParameters);
        
        // Obtém parâmetros do corpo da requisição para métodos POST/PUT/PATCH
        $bodyParameters = $this->getRequestBodyParameters($route, $methods);
        $parameters = array_merge($parameters, $bodyParameters);
        
        return $parameters;
    }

    /**
     * Obtém informações dos parâmetros da rota
     *
     * @param \Illuminate\Routing\Route $route
     * @return array
     */
    private function getRouteParameters($route): array
    {
        $parameters = [];
        
        // Obtém nomes dos parâmetros da rota
        $parameterNames = $route->parameterNames();
        
        if (!empty($parameterNames)) {
            foreach ($parameterNames as $paramName) {
                // Verifica se o parâmetro é opcional analisando o regex compilado da rota
                $isOptional = $this->isParameterOptional($route, $paramName);
                
                $parameters[] = [
                    'name' => $paramName,
                    'required' => !$isOptional,
                    'type' => $this->guessParameterType($paramName)
                ];
            }
        }

        return $parameters;
    }

    /**
     * Obtém parâmetros do corpo da requisição baseado na rota e métodos
     *
     * @param \Illuminate\Routing\Route $route
     * @param array $methods
     * @return array
     */
    private function getRequestBodyParameters($route, array $methods): array
    {
        $parameters = [];
        $uri = $route->uri();
        
        // Adiciona parâmetros do corpo apenas para métodos que tipicamente têm corpo de requisição
        $bodyMethods = array_intersect($methods, ['POST', 'PUT', 'PATCH']);
        if (empty($bodyMethods)) {
            return $parameters;
        }
        
        // Define parâmetros baseado nos padrões dos endpoints
        if (str_contains($uri, 'auth/login')) {
            $parameters = [
                ['name' => 'email', 'required' => true, 'type' => 'string'],
                ['name' => 'password', 'required' => true, 'type' => 'string']
            ];
        } elseif (str_contains($uri, 'auth/register')) {
            $parameters = [
                ['name' => 'name', 'required' => true, 'type' => 'string'],
                ['name' => 'email', 'required' => true, 'type' => 'string'],
                ['name' => 'password', 'required' => true, 'type' => 'string'],
                ['name' => 'password_confirmation', 'required' => true, 'type' => 'string']
            ];
        } elseif (str_contains($uri, 'users') && !str_contains($uri, '{')) {
            // POST /users (criar usuário)
            $parameters = [
                ['name' => 'name', 'required' => true, 'type' => 'string'],
                ['name' => 'email', 'required' => true, 'type' => 'string'],
                ['name' => 'password', 'required' => false, 'type' => 'string']
            ];
        } elseif (str_contains($uri, 'users/{') && in_array('PUT', $bodyMethods)) {
            // PUT /users/{id} (atualizar usuário)
            $parameters = [
                ['name' => 'name', 'required' => false, 'type' => 'string'],
                ['name' => 'email', 'required' => false, 'type' => 'string'],
                ['name' => 'password', 'required' => false, 'type' => 'string']
            ];
        }
        
        return $parameters;
    }

    /**
     * Verifica se um parâmetro é opcional
     *
     * @param \Illuminate\Routing\Route $route
     * @param string $paramName
     * @return bool
     */
    private function isParameterOptional($route, string $paramName): bool
    {
        // Verifica se o parâmetro tem um '?' no padrão original da URI
        $uri = $route->uri();
        return str_contains($uri, '{' . $paramName . '?}');
    }

    /**
     * Deduz o tipo do parâmetro baseado no nome
     *
     * @param string $paramName
     * @return string
     */
    private function guessParameterType(string $paramName): string
    {
        if (in_array($paramName, ['id', 'user_id', 'userId'])) {
            return 'integer';
        }
        
        if (str_ends_with($paramName, '_id') || str_ends_with($paramName, 'Id')) {
            return 'integer';
        }

        return 'string';
    }
}
