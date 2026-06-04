<?php

return [
    'default_user_name' => env('DEFAULT_USER_NAME', 'Admin'),
    'default_user_email' => env('DEFAULT_USER_EMAIL', 'admin@example.com'),
    'default_user_password' => env('DEFAULT_USER_PASSWORD', 'password'),
    'llm_api_url' => env('LLM_API_URL'),
    'llm_model' => env('LLM_MODEL'),
    'llm_chat_timeout' => env('LLM_CHAT_TIMEOUT', 120),
    'llm_embed_timeout' => env('LLM_EMBED_TIMEOUT', 180),
];
