<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Services\AiProviders\ClaudeProvider;
use App\Services\PromptBuilder;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void {
        $this->app->scoped(PromptBuilder::class);

        $this->app->scoped(AiProviderInterface::class, function ($app) {
            $defaultProviders = config('ai.default_providers');
            $promptBuilder = $app->make(PromptBuilder::class);

            return match ($defaultProviders) {
                'claude' => new ClaudeProvider($promptBuilder),
                default => new ClaudeProvider($promptBuilder),
            };
        });
    }
}
