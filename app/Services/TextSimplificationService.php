<?php

namespace App\Services;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TextSimplificationService
{
    /**
     * Constructor with dependency injection
     * Laravel automatically injects the correct provider based on AiServiceProvider
     */
    public function __construct(
        private AiProviderInterface $aiProvider
    ) {}

    /**
     * Simplify text with caching to reduce API costs
     */
    public function simplify(
        string $text,
        string $targetLevel,
        string $targetLanguage
    ): string {
        // Check if caching is enabled
        if (!config('ai.cache.enabled', true)) {
            return $this->aiProvider->simplifyText($text, $targetLevel, $targetLanguage);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($text, $targetLevel, $targetLanguage);
        $cacheTtl = config('ai.cache.ttl', 86400);

        // Try to get from cache, or execute and cache the result
        return Cache::remember($cacheKey, $cacheTtl, function () use ($text, $targetLevel, $targetLanguage) {
            Log::info('Cache miss - calling AI provider', [
                'provider' => $this->aiProvider->getName(),
                'target_level' => $targetLevel,
                'target_language' => $targetLanguage
            ]);

            return $this->aiProvider->simplifyText($text, $targetLevel, $targetLanguage);
        });
    }

    /**
     * Get word explanation with caching
     */
    public function explainWord(
        string $word,
        string $context,
        string $targetLanguage,
        string $nativeLanguage,
        string $proficiencyLevel
    ): array {
        if (!config('ai.cache.enabled', true)) {
            return $this->aiProvider->explainWord(
                $word,
                $context,
                $targetLanguage,
                $nativeLanguage,
                $proficiencyLevel
            );
        }

        $cacheKey = $this->generateWordCacheKey($word, $context, $targetLanguage, $proficiencyLevel);
        $cacheTtl = config('ai.cache.ttl', 86400);

        return Cache::remember($cacheKey, $cacheTtl, function () use (
            $word,
            $context,
            $targetLanguage,
            $nativeLanguage,
            $proficiencyLevel
        ) {
            Log::info('Cache miss - explaining word', [
                'provider' => $this->aiProvider->getName(),
                'word' => $word
            ]);

            return $this->aiProvider->explainWord(
                $word,
                $context,
                $targetLanguage,
                $nativeLanguage,
                $proficiencyLevel
            );
        });
    }

    /**
     * Clear cached simplification for specific parameters
     */
    public function clearSimplificationCache(
        string $text,
        string $targetLevel,
        string $targetLanguage
    ): void {
        $cacheKey = $this->generateCacheKey($text, $targetLevel, $targetLanguage);
        Cache::forget($cacheKey);

        Log::info('Cleared simplification cache', [
            'target_level' => $targetLevel,
            'target_language' => $targetLanguage
        ]);
    }

    /**
     * Clear all AI-related cache
     */
    public function clearAllCache(): void
    {
        Cache::flush();
        Log::info('Cleared all AI cache');
    }

    /**
     * Check if the current AI provider is available
     */
    public function isProviderAvailable(): bool
    {
        return $this->aiProvider->isAvailable();
    }

    /**
     * Get the current provider name
     */
    public function getProviderName(): string
    {
        return $this->aiProvider->getName();
    }

    /**
     * Generate cache key for text simplification
     */
    private function generateCacheKey(
        string $text,
        string $targetLevel,
        string $targetLanguage
    ): string {
        return sprintf(
            'ai:simplified:%s:%s:%s:%s',
            $this->aiProvider->getName(),
            md5($text),
            $targetLevel,
            strtolower($targetLanguage)
        );
    }

    /**
     * Generate cache key for word explanation
     */
    private function generateWordCacheKey(
        string $word,
        string $context,
        string $targetLanguage,
        string $proficiencyLevel
    ): string {
        return sprintf(
            'ai:word:%s:%s:%s:%s:%s',
            $this->aiProvider->getName(),
            md5(strtolower($word)),
            md5($context),
            strtolower($targetLanguage),
            $proficiencyLevel
        );
    }
}
