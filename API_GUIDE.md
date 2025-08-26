# API Soluções Urbanas - Guia de Testes

## URLs da API

**Base URL:** `https://api-solucoes-urbanas.vercel.app/api/`

## Rotas Disponíveis

### ✅ 1. Teste da API
- **GET** `/debug` - Informações do servidor PHP
- **GET** `/test` - Teste do Laravel (endpoint do controller TestController)
- **Exemplo:** 
  - `https://api-solucoes-urbanas.vercel.app/api/debug` ✅ Funcionando
  - `https://api-solucoes-urbanas.vercel.app/api/test` 🔄 Testando agora

### 🔐 2. Autenticação

#### Registrar usuário
- **POST** `/auth/register`
- **URL:** `https://api-solucoes-urbanas.vercel.app/api/auth/register`
- **Body (JSON):**
```json
{
    "username": "usuario_teste",
    "email": "teste@email.com",
    "password": "123456",
    "full_name": "Nome Completo",
    "cpf": "12345678901",
    "birth_date": "1990-01-01",
    "type": "user"
}
```

#### Login
- **POST** `/auth/login`
- **URL:** `https://api-solucoes-urbanas.vercel.app/api/auth/login`
- **Body (JSON):**
```json
{
    "email": "teste@email.com",
    "password": "123456"
}
```

#### Obter dados do usuário logado
- **GET** `/auth/me`
- **Headers:** 
  - `Authorization: Bearer {token}`

### 👥 3. Usuários (Requer autenticação)

#### Listar usuários
- **GET** `/users`
- **Headers:** 
  - `Authorization: Bearer {token}`

## Status dos Endpoints:

✅ **Funcionando:** `/api/debug`  
🔄 **Em teste:** `/api/test` (agora com Laravel)  
🔄 **Em teste:** `/api/auth/login`  
🔄 **Em teste:** `/api/auth/register`  

## Como testar:

1. **Verifique se o Laravel está funcionando:**
   ```bash
   curl https://api-solucoes-urbanas.vercel.app/api/test
   ```

2. **Teste o registro:**
   ```bash
   curl -X POST https://api-solucoes-urbanas.vercel.app/api/auth/register \
     -H "Content-Type: application/json" \
     -d '{"username":"teste","email":"teste@test.com","password":"123456"}'
   ```

3. **Teste o login:**
   ```bash
   curl -X POST https://api-solucoes-urbanas.vercel.app/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"teste@test.com","password":"123456"}'
   ```