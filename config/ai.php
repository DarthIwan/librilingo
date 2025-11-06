<?php

return [
    /**
    *--------------------------------------------------------------------------
    * Default AI Provider
    *--------------------------------------------------------------------------
    *
    * This option controls which AI provider will be used by default.
    * Currently supported: "claude"
    * Future: "openai", "gemini", etc.
    *
    */
    'default_provider' => env('AI_PROVIDER', 'claude'),

    /**
    *--------------------------------------------------------------------------
    * AI Providers Configuration
    *--------------------------------------------------------------------------
    *
    * Configuration for each AI provider. Add new providers here as you
    * integrate them. Each provider should have at minimum an 'api_key'.
    *
    */
    'providers' => [
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude_sonnet-4-20250514'),
            'max_tokens' => env('CLAUDE_MAX_TOKENS', 4000),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-5'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
        ],
    ],

    /**
    *--------------------------------------------------------------------------
    * Caching Configuration
    *--------------------------------------------------------------------------
    *
    * Enable caching to avoid redundant API calls for the same requests.
    * This significantly reduces costs and improves response times.
    *
    */
    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 86400),
    ]
];
