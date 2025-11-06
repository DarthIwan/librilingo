<?php

namespace App\Contracts;

interface AiProviderInterface {
    /**
     * Simplify text to a specific CEFR proficiency level
     *
     * @param string $text The original text to simplify
     * @param string $targetLevel CEFR level (A1, A2, B1, B2, C1, C2)
     * @param string $targetLanguage The language being learned (e.g., 'English', 'Spanish')
     * @return string The simplified text
     * @throws \Exception If the API call fails
     */
    public function simplifyText(
        string $text,
        string $targetLevel,
        string $targetLanguage
    ): string;

    /**
     * Get comprehensive word explanation with translation and examples
     *
     * @param string $word The word to explain
     * @param string $context The full sentence containing the word
     * @param string $targetLanguage The language being learned
     * @param string $nativeLanguage User's native language for translation
     * @param string $proficiencyLevel Current CEFR proficiency level
     * @return array Array containing: translation, pronunciation, partOfSpeech,
     *               definition, exampleSentences, grammarNote
     * @throws \Exception If the API call fails
     */
    public function explainWord(
        string $word,
        string $context,
        string $targetLanguage,
        string $nativeLanguage,
        string $proficiencyLevel
    ): array;

    /**
     * Check if the provider is properly configured and available
     *
     * @return bool True if API key is set and provider is ready
     */
    public function isAvailable(): bool;

    /**
     * Get the provider's name for logging/debugging
     *
     * @return string Provider name (e.g., 'claude', 'openai')
     */
    public function getName(): string;
}
