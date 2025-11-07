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
        $this->promptBuilder = $promptBuilder;

        $apiKey = config('ai.providers.claude.api_key');

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
        try {
            // Build prompt from template using buildUserPrompt (not build)
            $prompt = $this->promptBuilder->buildUserPrompt(
                'word-explanation',
                'default',
                [
                    'word' => $word,
                    'context' => $context,
                    'targetLanguage' => $targetLanguage,
                    'nativeLanguage' => $nativeLanguage,
                    'proficiencyLevel' => $proficiencyLevel,
                ]
            );

            Log::debug('Calling Claude for word explanation', [
                'word' => $word,
                'context' => $context
            ]);

            $response = $this->client->messages->create(
                maxTokens: 1500,
                messages: [MessageParam::with(content: $prompt, role: 'user')],
                model: $this->model,
            );

            $content = $response->content[0]->text;

            // Log the raw response for debugging
            Log::debug('Raw Claude response for word explanation', [
                'raw_content' => $content
            ]);

            // Clean up any markdown formatting more aggressively
            $content = preg_replace('/^```json\s*/m', '', $content);
            $content = preg_replace('/^```\s*/m', '', $content);
            $content = preg_replace('/```$/m', '', $content);
            $content = trim($content);

            // Remove any text before the first {
            if (($pos = strpos($content, '{')) !== false && $pos > 0) {
                $content = substr($content, $pos);
            }

            // Remove any text after the last }
            if (($pos = strrpos($content, '}')) !== false) {
                $content = substr($content, 0, $pos + 1);
            }

            // Log cleaned content
            Log::debug('Cleaned response', [
                'cleaned_content' => $content
            ]);

            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON parsing failed', [
                    'error' => json_last_error_msg(),
                    'content' => substr($content, 0, 500)
                ]);
                throw new \Exception('Failed to parse JSON response: ' . json_last_error_msg());
            }

            // Validate result is an array
            if (!is_array($result)) {
                Log::error('Result is not an array', [
                    'type' => gettype($result),
                    'content' => substr($content, 0, 500)
                ]);
                throw new \Exception('Response is not a valid array');
            }

            // Validate expected keys exist
            $requiredKeys = ['word', 'translation', 'pronunciation', 'partOfSpeech', 'definition', 'exampleSentences', 'grammarNote'];
            $missingKeys = array_diff($requiredKeys, array_keys($result));

            if (!empty($missingKeys)) {
                Log::error('Missing required keys in response', [
                    'missing_keys' => $missingKeys,
                    'received_keys' => array_keys($result),
                    'content' => substr($content, 0, 500)
                ]);
                throw new \Exception('Response missing required keys: ' . implode(', ', $missingKeys));
            }

            Log::info('Word explained successfully', [
                'provider' => 'claude',
                'word' => $word,
                'language' => $targetLanguage
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Claude API Error in explainWord', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'word' => $word,
                'context' => $context,
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception - this will propagate up
            throw $e;
        }
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
