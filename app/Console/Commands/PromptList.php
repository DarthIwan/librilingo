<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PromptBuilder;

class PromptList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prompt:list {category?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available prompt templates';

    /**
     * Execute the console command.
     */
    public function handle(PromptBuilder $promptBuilder)
    {
        $category = $this->argument('category');

        if($category) {
            $this->listCategory($category, $promptBuilder);
        } else {
            $this->listAllCategories();
        }
    }

    private function listAllCategories(): void
    {
        $this->info('Available Prompt Categories:');
        $this->newLine();

        $categories = [
            'text-simplification' => 'Text simplification to CEFR levels',
            'word-explanation' => 'Word definitions and examples',
        ];

        foreach ($categories as $name => $description) {
            $this->line("  📁 {$name}");
            $this->line("     {$description}");
            $this->newLine();
        }

        $this->info('Use: php artisan prompt:list {category} to see templates');
    }

    private function listCategory(string $category, PromptBuilder $promptBuilder): void
    {
        $templates = $promptBuilder->listTemplates($category);

        if (empty($templates)) {
            $this->error("No templates found in category: {$category}");
            return;
        }

        $this->info("Templates in '{$category}':");
        $this->newLine();

        foreach ($templates as $template) {
            $this->line("  📄 {$template}.json");
        }
    }
}
