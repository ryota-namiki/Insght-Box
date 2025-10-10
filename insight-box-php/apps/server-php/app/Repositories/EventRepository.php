<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class EventRepository
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        $events = DB::table('events')
            ->orderBy('start_date', 'desc')
            ->get()
            ->toArray();
        
        return array_map(function($event) {
            return (array) $event;
        }, $events);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(string $id): ?array
    {
        $event = DB::table('events')->where('id', $id)->first();
        
        return $event ? (array) $event : null;
    }

    /**
     * @param array<string,mixed> $event
     */
    public function create(array $event): void
    {
        DB::table('events')->insert([
            'id' => $event['id'],
            'name' => $event['name'],
            'description' => $event['description'] ?? null,
            'location' => $event['location'] ?? null,
            'start_date' => $event['startDate'] ?? $event['start_date'],
            'end_date' => $event['endDate'] ?? $event['end_date'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string,mixed> $event
     */
    public function update(string $id, array $event): void
    {
        $data = [
            'updated_at' => now(),
        ];
        
        if (isset($event['name'])) {
            $data['name'] = $event['name'];
        }
        if (isset($event['description'])) {
            $data['description'] = $event['description'];
        }
        if (isset($event['location'])) {
            $data['location'] = $event['location'];
        }
        if (isset($event['startDate'])) {
            $data['start_date'] = $event['startDate'];
        }
        if (isset($event['start_date'])) {
            $data['start_date'] = $event['start_date'];
        }
        if (isset($event['endDate'])) {
            $data['end_date'] = $event['endDate'];
        }
        if (isset($event['end_date'])) {
            $data['end_date'] = $event['end_date'];
        }
        
        DB::table('events')->where('id', $id)->update($data);
    }

    public function delete(string $id): void
    {
        DB::table('events')->where('id', $id)->delete();
    }
}
