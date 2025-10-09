<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;

class MigrateCardsFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cards:migrate-from-json {--force : Force migration even if cards already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate cards from JSON file to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonPath = storage_path('app/data/cards.json');
        
        if (!file_exists($jsonPath)) {
            $this->error('JSON file not found: ' . $jsonPath);
            return 1;
        }

        // Check if cards already exist
        if (Card::count() > 0 && !$this->option('force')) {
            $this->error('Cards already exist in database. Use --force to override.');
            return 1;
        }

        $this->info('Reading JSON file...');
        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        if (!$data) {
            $this->error('Failed to parse JSON file');
            return 1;
        }

        $this->info('Found ' . count($data) . ' cards. Starting migration...');
        $bar = $this->output->createProgressBar(count($data));

        $successCount = 0;
        $errorCount = 0;

        foreach ($data as $id => $record) {
            try {
                Card::updateOrCreate(['id' => $id], [
                    'id' => $id,
                    'title' => $record['summary']['title'] ?? '',
                    'company' => $record['summary']['company'] ?? null,
                    'tags' => $record['summary']['tags'] ?? [],
                    'event_id' => $record['summary']['eventId'] ?? '',
                    'author_id' => $record['summary']['authorId'] ?? null,
                    'status' => $record['summary']['status'] ?? 'draft',
                    'memo' => $record['detail']['memo'] ?? null,
                    'ocr_text' => $record['detail']['text'] ?? null,
                    'raw_text' => $record['detail']['rawText'] ?? null,
                    'document_id' => $record['detail']['documentId'] ?? null,
                    'camera_image' => $record['detail']['cameraImage'] ?? null,
                    'likes' => $record['reactions']['likes'] ?? 0,
                    'comments' => $record['reactions']['comments'] ?? 0,
                    'views' => $record['reactions']['views'] ?? 0,
                    'position_x' => $record['position']['x'] ?? null,
                    'position_y' => $record['position']['y'] ?? null,
                    'created_at' => isset($record['summary']['createdAt']) 
                        ? \Carbon\Carbon::parse($record['summary']['createdAt']) 
                        : now(),
                    'updated_at' => isset($record['summary']['updatedAt']) 
                        ? \Carbon\Carbon::parse($record['summary']['updatedAt']) 
                        : now(),
                ]);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nFailed to migrate card {$id}: " . $e->getMessage());
                $errorCount++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Migration completed!");
        $this->info("Success: {$successCount}, Errors: {$errorCount}");

        return 0;
    }
}
