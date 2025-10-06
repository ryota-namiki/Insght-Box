<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

class CardRepository
{
    private string $path = 'data/cards.json';

    /** @return array<string,mixed> */
    private function readData(): array
    {
        $fullPath = storage_path('app/' . $this->path);
        if (!file_exists($fullPath)) {
            return [];
        }
        $json = file_get_contents($fullPath);
        return $json ? json_decode($json, true) : [];
    }

    /** @return array<string,mixed> */
    public function read(): array
    {
        return $this->readData();
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

    /** @return array<int,array<string,mixed>> */
    public function listSummaries(): array
    {
        $all = $this->readData();
        return array_map(fn($r) => $r['summary'] ?? [], array_values($all));
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        $all = $this->readData();
        return $all[$id] ?? null;
    }

    /** @param array<string,mixed> $record */
    public function upsert(string $id, array $record): void
    {
        $all = $this->readData();
        $all[$id] = $record;
        $this->write($all);
    }

    public function delete(string $id): void
    {
        $all = $this->readData();
        unset($all[$id]);
        $this->write($all);
    }

    public function updatePosition(string $id, int $x, int $y): void
    {
        $all = $this->readData();
        if (isset($all[$id])) {
            $all[$id]['position'] = ['x' => $x, 'y' => $y];
            $all[$id]['updated_at'] = now()->toIso8601String();
            $this->write($all);
        }
    }
}
