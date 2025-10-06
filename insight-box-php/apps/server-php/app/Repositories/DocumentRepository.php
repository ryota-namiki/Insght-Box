<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

class DocumentRepository
{
    private string $path = 'data/documents.json';

    /** @return array<string,mixed> */
    private function read(): array
    {
        $fullPath = storage_path('app/' . $this->path);
        if (!file_exists($fullPath)) {
            return [];
        }
        $json = file_get_contents($fullPath);
        return $json ? json_decode($json, true) : [];
    }

    /** @param array<string,mixed> $data */
    private function write(array $data): void
    {
        $fullPath = storage_path('app/' . $this->path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        $all = $this->read();
        return $all[$id] ?? null;
    }

    /** @param array<string,mixed> $record */
    public function upsert(string $id, array $record): void
    {
        $all = $this->read();
        $all[$id] = $record;
        $this->write($all);
    }

    public function updateText(string $id, string $text): void
    {
        $all = $this->read();
        if (isset($all[$id])) {
            $all[$id]['text'] = $text;
            $all[$id]['updated_at'] = now()->toIso8601String();
            $this->write($all);
        }
    }

    /** @param array<string,mixed> $metadata */
    public function updateMetadata(string $id, array $metadata): void
    {
        $all = $this->read();
        if (isset($all[$id])) {
            $all[$id]['metadata'] = $metadata;
            $all[$id]['updated_at'] = now()->toIso8601String();
            $this->write($all);
        }
    }

    public function delete(string $id): void
    {
        $all = $this->read();
        unset($all[$id]);
        $this->write($all);
    }
}
