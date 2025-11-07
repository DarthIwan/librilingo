<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class PromptBuilder
{
    private const PROMPT_PATH = 'resources/prompts/';
    private const CACHE_TTL = 3600;

    /**
     * Load and build a prompt from a template
     *
     * @param string $category Prompt category (e.g., 'text-simplification')
     * @param string $template Template name (e.g., 'default')
     * @param array $variables Variables to replace in the template
     * @return array ['system' => '...', 'user' => '...']
     * @throws Exception
     */
    public function build(string $category, string $template, array $variables): array
    {
        $promptData = $this->loadTemplate($category, $template);

        // Validate required variables
        $this->validateVariables($promptData['variables'] ?? [], $variables);

        // Replace variables in both system and user prompts
        $systemPrompt = $this->replaceVariables(
            $promptData['template']['system'] ?? '',
            $variables
        );

        $userPrompt = $this->replaceVariables(
            $promptData['template']['user'] ?? '',
            $variables
        );

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
            'metadata' => $promptData['metadata'] ?? [],
            'output_format' => $promptData['output_format'] ?? 'text'
        ];
    }

    /**
     * Get the user prompt only (for APIs that don't support system prompts)
     *
     * @param string $category
     * @param string $template
     * @param array $variables
     * @return string
     * @throws Exception
     */
    public function buildUserPrompt(string $category, string $template, array $variables): string
    {
        $promptData = $this->loadTemplate($category, $template);
        $this->validateVariables($promptData['variables'] ?? [], $variables);

        // Combine system and user prompts for APIs without system role
        $systemPrompt = $promptData['template']['system'] ?? '';
        $userPrompt = $promptData['template']['user'] ?? '';

        $combined = !empty($systemPrompt)
            ? $systemPrompt . "\n\n" . $userPrompt
            : $userPrompt;

        return $this->replaceVariables($combined, $variables);
    }

    /**
     * Load a prompt template from JSON file with caching
     *
     */
    private function loadTemplate(string $category, string $template): array {
        $cacheKey = "prompt_template:$category:$template";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category, $template) {
            $path = base_path(self::PROMPT_PATH . "/{$category}/{$template}.json");

            if (!File::exists($path)) {
                throw new Exception("Prompt template not found: {$category}/{$template}");
            }

            $content = File::get($path);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in prompt template: " . json_last_error_msg());
            }

            return $data;
        });
    }

    /**
     * Replace variables in a template string
     *
     * @param string $template
     * @param array $variables
     * @return string
     */
    private function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    /**
     * Validate that all required variables are provided
     *
     * @param array $required
     * @param array $provided
     * @throws Exception
     */
    private function validateVariables(array $required, array $provided): void
    {
        $missing = array_diff($required, array_keys($provided));

        if (!empty($missing)) {
            throw new Exception(
                "Missing required variables for prompt: " . implode(', ', $missing)
            );
        }
    }

    /**
     * Clear the prompt template cache
     */
    public function clearCache(): void
    {
        Cache::forget('prompt_template:*');
    }

    /**
     * List all available templates in a category
     *
     * @param string $category
     * @return array
     */
    public function listTemplates(string $category): array
    {
        $path = base_path(self::PROMPT_PATH . "/{$category}");

        if (!File::isDirectory($path)) {
            return [];
        }

        $files = File::files($path);
        $templates = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $templates[] = $file->getBasename('.json');
            }
        }

        return $templates;
    }
}
