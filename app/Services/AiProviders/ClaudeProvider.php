<?php

namespace App\Services\AiProviders;

use Anthropic\Client;
use Anthropic\Messages\MessageParam;
use App\Contracts\AiProviderInterface;
use App\Services\PromptBuilder;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AiProviderInterface {

    private Client $client;
    private string $model;
    private int $maxTokens;
    private PromptBuilder $promptBuilder;

    /**
     * Initialize the Claude client with configuration
     */
    public function __construct(PromptBuilder $promptBuilder) {
        $this->$promptBuilder = $promptBuilder;

        $apiKey = config('services.claude.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Claude api key is missing');
        }

        $this->client = new Client($apiKey);

        // Get model settings from config
        $this->model = config('ai.providers.claude.model', 'claude-sonnet-4-20250514');
        $this->maxTokens = config('ai.providers.claude.max_tokens', 4000);
    }

    /**
     * Simplify text to target proficiency level
     * @throws \Exception
     */
    public function simplifyText(string $text, string $targetLevel, string $targetLanguage): string
    {
        try {
            $prompt = $this->promptBuilder->buildUserPrompt(
                'text-simplification',
                'default',
                [
                    'text' => $text,
                    'targetLevel' => $targetLevel,
                    'targetLanguage' => $targetLanguage,
                ]
            );

            $response = $this->client->messages->create(
                maxTokens: $this->maxTokens,
                messages: [MessageParam::with(content: $prompt, role: 'user')],
                model: $this->model,
            );

            $simplifiedText = $response->content[0]->text;

            Log::info('Text simplified successfully', [
                'provider' => 'claude',
                'model' => $this->model,
                'target_level' => $targetLevel,
                'original_length' => strlen($text),
                'simplified_length' => strlen($simplifiedText),
            ]);

            return $simplifiedText;

        } catch (\Exception $e) {
            Log::error('Claude API Error in simplifyText', [
                'error' => $e->getMessage(),
                'target_level' => $targetLevel,
                'target_language' => $targetLanguage,
                'text_length' => strlen($text)
            ]);

            throw new \Exception('Failed to simplify text: ' . $e->getMessage());
        }
    }

    public function explainWord(string $word, string $context, string $targetLanguage, string $nativeLanguage, string $proficiencyLevel): array
    {
        // TODO: Implement explainWord() method.
    }

    public function isAvailable(): bool
    {
        return !empty(config('ai.providers.claude.api_key'));
    }

    public function getName(): string
    {
        return 'claude';
    }


}
