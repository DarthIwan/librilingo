<?php

namespace App\Console\Commands;

use App\Services\TextSimplificationService;
use Illuminate\Console\Command;

class TestAiProvider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test {--no-cache : Disable cache for this test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI provider implementation';

    /**
     * Execute the console command.
     */
    public function handle(TextSimplificationService $service): void
    {
        $this->info('🧪 Testing AI Provider Implementation');
        $this->newLine();

        // Check if provider is available
        if (!$service->isProviderAvailable()) {
            $this->error('❌ AI Provider is not available. Check your ANTHROPIC_API_KEY in .env');
            return;
        }

        $this->info('✅ Provider available: ' . $service->getProviderName());
        $this->newLine();

        // Test 1: Simple text simplification
        $this->info('📝 Test 1: Text Simplification');
        $this->line('─────────────────────────────');

        $testText = "The magnificent feline perched gracefully upon the woven textile floor covering, observing the avian creatures as they soared through the celestial expanse.";

        $this->line('Original (C2 level): ' . $testText);
        $this->newLine();

        try {
            $this->info('Simplifying to A1 level...');
            $simplified = $service->simplify($testText, 'A1', 'English');

            $this->line('Simplified (A1): ' . $simplified);
            $this->newLine();
            $this->info('✅ Test 1 passed!');

        } catch (\Exception $e) {
            $this->error('❌ Test 1 failed: ' . $e->getMessage());
            return;
        }

        $this->newLine(2);

        // Test 2: Word explanation
        $this->info('📚 Test 2: Word Explanation');
        $this->line('─────────────────────────────');

        try {
            $this->info('Getting explanation for "magnificent"...');
            $explanation = $service->explainWord(
                word: 'magnificent',
                context: 'The magnificent castle stood on the hill.',
                targetLanguage: 'English',
                nativeLanguage: 'German',
                proficiencyLevel: 'B1'
            );

            $this->line('Word: ' . $explanation['word']);
            $this->line('Translation: ' . $explanation['translation']);
            $this->line('Part of Speech: ' . $explanation['partOfSpeech']);
            $this->line('Definition: ' . $explanation['definition']);
            $this->newLine();
            $this->line('Example sentences:');
            foreach ($explanation['exampleSentences'] as $index => $example) {
                $this->line('  ' . ($index + 1) . '. ' . $example);
            }

            $this->newLine();
            $this->info('✅ Test 2 passed!');

        } catch (\Exception $e) {
            $this->error('❌ Test 2 failed: ' . $e->getMessage());
            return;
        }

        $this->newLine(2);
        $this->info('🎉 All tests passed successfully!');
    }
}
