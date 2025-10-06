<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

class EventRepository
{
    private string $path = 'data/events.json';

    /**
     * @return array<string,mixed>
     */
    private function read(): array
    {
        $fullPath = storage_path('app/' . $this->path);
        if (!file_exists($fullPath)) {
            return [];
        }
        
        $json = file_get_contents($fullPath);
        return $json ? json_decode($json, true) : [];
    }

    /**
     * @param array<string,mixed> $data
     */
    private function write(array $data): void
    {
        $fullPath = storage_path('app/' . $this->path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents(
            $fullPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        $all = $this->read();
        return array_values($all);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(string $id): ?array
    {
        $all = $this->read();
        return $all[$id] ?? null;
    }

    /**
     * @param array<string,mixed> $event
     */
    public function create(array $event): void
    {
        $all = $this->read();
        $all[$event['id']] = $event;
        $this->write($all);
    }

    /**
     * @param array<string,mixed> $event
     */
    public function update(string $id, array $event): void
    {
        $all = $this->read();
        if (isset($all[$id])) {
            $all[$id] = array_merge($all[$id], $event);
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
