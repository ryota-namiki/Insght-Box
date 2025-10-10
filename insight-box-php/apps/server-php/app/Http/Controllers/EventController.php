<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\EventRepository;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(EventRepository $repo)
    {
        $events = $repo->list();
        return view('events.index', ['events' => $events]);
    }

    public function create()
    {
        return view('events.create');
    }

    public function store(Request $request, EventRepository $repo)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $eventId = (string) Str::uuid();

        $event = [
            'id' => $eventId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ];

        $repo->create($event);

        return redirect()->route('events.index')
            ->with('success', 'イベントが作成されました');
    }

    public function edit(string $id, EventRepository $repo)
    {
        $event = $repo->find($id);
        
        if (!$event) {
            abort(404);
        }

        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request, string $id, EventRepository $repo)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $repo->update($id, [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        return redirect()->route('events.index')
            ->with('success', 'イベントが更新されました');
    }

    public function destroy(string $id, EventRepository $repo)
    {
        $repo->delete($id);

        return redirect()->route('events.index')
            ->with('success', 'イベントが削除されました');
    }
}

