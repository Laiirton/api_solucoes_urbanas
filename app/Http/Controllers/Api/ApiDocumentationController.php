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
        
        // Adiciona parâmetros do corpo apenas para métodos que tipicamente têm corpo de requisição
        $bodyMethods = array_intersect($methods, ['POST', 'PUT', 'PATCH']);
        if (empty($bodyMethods)) {
            return $parameters;
        }
        
        try {
            // Tenta obter parâmetros automaticamente do controller
            $parameters = $this->getParametersFromController($route, $bodyMethods);
        } catch (\Exception $e) {
            // Se falhar, retorna parâmetros genéricos baseados no método HTTP
            $parameters = $this->getGenericParameters($bodyMethods);
        }
        
        return $parameters;
    }

    /**
     * Obtém parâmetros automaticamente do controller usando reflection
     *
     * @param \Illuminate\Routing\Route $route
     * @param array $bodyMethods
     * @return array
     */
    private function getParametersFromController($route, array $bodyMethods): array
    {
        $parameters = [];
        $action = $route->getAction();
        
        if (!isset($action['controller'])) {
            return $this->getGenericParameters($bodyMethods);
        }
        
        [$controllerClass, $method] = explode('@', $action['controller']);
        
        if (!class_exists($controllerClass)) {
            return $this->getGenericParameters($bodyMethods);
        }
        
        $reflection = new \ReflectionClass($controllerClass);
        
        if (!$reflection->hasMethod($method)) {
            return $this->getGenericParameters($bodyMethods);
        }
        
        $methodReflection = $reflection->getMethod($method);
        $methodParameters = $methodReflection->getParameters();
        
        // Primeiro tenta detectar FormRequests
        foreach ($methodParameters as $param) {
            $paramType = $param->getType();
            
            if ($paramType && $paramType instanceof \ReflectionNamedType && !$paramType->isBuiltin()) {
                $paramClassName = $paramType->getName();
                
                if (is_subclass_of($paramClassName, 'Illuminate\Foundation\Http\FormRequest')) {
                    $parameters = array_merge($parameters, $this->getParametersFromFormRequest($paramClassName));
                }
            }
        }
        
        // Se não encontrou FormRequests, tenta extrair validação inline do código
        if (empty($parameters)) {
            $parameters = $this->getParametersFromInlineValidation($controllerClass, $method);
        }
        
        // Se ainda não encontrou parâmetros específicos, retorna genéricos
        if (empty($parameters)) {
            $parameters = $this->getGenericParameters($bodyMethods);
        }
        
        return $parameters;
    }

    /**
     * Obtém parâmetros de validação inline do código do controller
     *
     * @param string $controllerClass
     * @param string $method
     * @return array
     */
    private function getParametersFromInlineValidation(string $controllerClass, string $method): array
    {
        $parameters = [];
        
        try {
            $reflection = new \ReflectionMethod($controllerClass, $method);
            $filename = $reflection->getFileName();
            $startLine = $reflection->getStartLine();
            $endLine = $reflection->getEndLine();
            
            if (!$filename || !$startLine || !$endLine) {
                return $parameters;
            }
            
            $lines = file($filename);
            $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
            
            // Procura por padrões de validação Validator::make - versão mais simples e robusta
            if (preg_match('/Validator::make\s*\(\s*[^,]+\s*,\s*\[(.*?)\]\s*\)/s', $methodCode, $matches)) {
                $rulesString = $matches[1];
                $parameters = $this->parseValidationRules($rulesString);
            }
            
            // Se não encontrou com Validator::make, tenta com $this->validate ou $request->validate
            if (empty($parameters)) {
                if (preg_match('/(?:\$this->validate|\$request->validate)\s*\(\s*[^,]*\s*,\s*\[(.*?)\]/s', $methodCode, $matches)) {
                    $rulesString = $matches[1];
                    $parameters = $this->parseValidationRules($rulesString);
                }
            }
            
        } catch (\Exception $e) {
            // Se falhar, retorna array vazio
        }
        
        return $parameters;
    }

    /**
     * Faz parse das regras de validação inline
     *
     * @param string $rulesString
     * @return array
     */
    private function parseValidationRules(string $rulesString): array
    {
        $parameters = [];
        
        // Normaliza o string removendo quebras de linha e espaços extras
        $rulesString = preg_replace('/\s+/', ' ', $rulesString);
        
        // Padrão mais robusto para capturar 'field' => ['rules'] ou "field" => ['rules']
        preg_match_all("/['\"]([^'\"]+)['\"]\\s*=>\\s*\\[([^\\]]+)\\]/", $rulesString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $fieldName = $match[1];
            $rulesArray = $match[2];
            
            // Parse das regras individuais
            preg_match_all("/['\"]([^'\"]+)['\"]/", $rulesArray, $ruleMatches);
            $rules = $ruleMatches[1] ?? [];
            
            // Verifica se é obrigatório
            $isRequired = false;
            foreach ($rules as $rule) {
                if ($rule === 'required' || str_starts_with($rule, 'required_')) {
                    $isRequired = true;
                    break;
                }
            }
            
            $type = $this->getFieldTypeFromRules($fieldName, $rules);
            
            $parameters[] = [
                'name' => $fieldName,
                'required' => $isRequired,
                'type' => $type
            ];
        }
        
        return $parameters;
    }

    /**
     * Obtém o tipo do campo baseado nas regras de validação
     *
     * @param string $fieldName
     * @param array $rules
     * @return string
     */
    private function getFieldTypeFromRules(string $fieldName, array $rules): string
    {
        foreach ($rules as $rule) {
            if (str_contains($rule, 'email')) {
                return 'email';
            }
            if (str_contains($rule, 'integer') || str_contains($rule, 'numeric')) {
                return 'integer';
            }
            if (str_contains($rule, 'boolean')) {
                return 'boolean';
            }
            if (str_contains($rule, 'date')) {
                return 'date';
            }
        }
        
        // Baseado no nome do campo
        if (str_contains($fieldName, 'email')) {
            return 'email';
        }
        if (str_contains($fieldName, 'password')) {
            return 'password';
        }
        if (str_contains($fieldName, 'date')) {
            return 'date';
        }
        if (in_array($fieldName, ['id', 'user_id', 'age', 'count', 'number'])) {
            return 'integer';
        }
        
        return 'string';
    }

    /**
     * Obtém parâmetros de um FormRequest
     *
     * @param string $formRequestClass
     * @return array
     */
    private function getParametersFromFormRequest(string $formRequestClass): array
    {
        $parameters = [];
        
        try {
            $formRequest = new $formRequestClass();
            
            if (method_exists($formRequest, 'rules')) {
                $rules = $formRequest->rules();
                
                foreach ($rules as $field => $rule) {
                    $isRequired = $this->isFieldRequired($rule);
                    $type = $this->getFieldType($field, $rule);
                    
                    $parameters[] = [
                        'name' => $field,
                        'required' => $isRequired,
                        'type' => $type
                    ];
                }
            }
        } catch (\Exception $e) {
            // Se falhar, não adiciona parâmetros
        }
        
        return $parameters;
    }

    /**
     * Verifica se um campo é obrigatório baseado nas regras de validação
     *
     * @param mixed $rule
     * @return bool
     */
    private function isFieldRequired($rule): bool
    {
        if (is_string($rule)) {
            return str_contains($rule, 'required');
        }
        
        if (is_array($rule)) {
            return in_array('required', $rule);
        }
        
        return false;
    }

    /**
     * Obtém o tipo do campo baseado no nome e regras
     *
     * @param string $fieldName
     * @param mixed $rule
     * @return string
     */
    private function getFieldType(string $fieldName, $rule): string
    {
        $ruleString = is_array($rule) ? implode('|', $rule) : (string) $rule;
        
        // Verifica regras específicas
        if (str_contains($ruleString, 'integer') || str_contains($ruleString, 'numeric')) {
            return 'integer';
        }
        
        if (str_contains($ruleString, 'email')) {
            return 'email';
        }
        
        if (str_contains($ruleString, 'boolean')) {
            return 'boolean';
        }
        
        // Baseado no nome do campo
        if (str_contains($fieldName, 'email')) {
            return 'email';
        }
        
        if (str_contains($fieldName, 'password')) {
            return 'password';
        }
        
        if (in_array($fieldName, ['id', 'user_id', 'age', 'count', 'number'])) {
            return 'integer';
        }
        
        return 'string';
    }

    /**
     * Retorna parâmetros genéricos baseado nos métodos HTTP
     *
     * @param array $bodyMethods
     * @return array
     */
    private function getGenericParameters(array $bodyMethods): array
    {
        // Para métodos que criam/atualizam recursos, sugere campos comuns
        if (array_intersect($bodyMethods, ['POST', 'PUT', 'PATCH'])) {
            return [
                ['name' => 'data', 'required' => false, 'type' => 'object']
            ];
        }
        
        return [];
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
