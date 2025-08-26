<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Ajuste para o(s) domínio(s) do seu frontend em produção, ex.:
    // 'allowed_origins' => ['https://seu-frontend.com'],
    // Mantendo aberto por padrão para facilitar testes iniciais:
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
