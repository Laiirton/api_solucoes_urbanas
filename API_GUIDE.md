# API Soluções Urbanas - Guia de Testes

## URLs da API

**Base URL:** `https://api-solucoes-urbanas.vercel.app/api/`

## Rotas Disponíveis

### 1. Teste da API
- **GET** `/test`
- **Descrição:** Endpoint simples para verificar se a API está funcionando
- **Exemplo:** `https://api-solucoes-urbanas.vercel.app/api/test`

### 2. Autenticação

#### Registrar usuário
- **POST** `/auth/register`
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

### 3. Usuários (Requer autenticação)

#### Listar usuários
- **GET** `/users`
- **Headers:** 
  - `Authorization: Bearer {token}`

#### Obter usuário específico
- **GET** `/users/{id}`
- **Headers:** 
  - `Authorization: Bearer {token}`

#### Atualizar usuário
- **PUT** `/users/{id}`
- **Headers:** 
  - `Authorization: Bearer {token}`
- **Body (JSON):** dados do usuário para atualizar

#### Deletar usuário
- **DELETE** `/users/{id}`
- **Headers:** 
  - `Authorization: Bearer {token}`

## Como testar

1. Primeiro, acesse `https://api-solucoes-urbanas.vercel.app/api/test` para verificar se a API está funcionando
2. Registre um usuário usando `/auth/register`
3. Faça login com `/auth/login` para obter o token
4. Use o token nas requisições protegidas

## Ferramentas recomendadas para teste:
- Postman
- Insomnia
- curl
- Thunder Client (VS Code)