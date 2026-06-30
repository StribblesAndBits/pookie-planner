<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('start')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
        ]);

        $this->validateDateTimeRange($validated['start'], $validated['start_time'], $validated['end'], $validated['end_time']);

        $event = Event::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($event, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $event = Event::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
        ]);

        $this->validateDateTimeRange($validated['start'], $validated['start_time'], $validated['end'], $validated['end_time']);

        $event->update($validated);

        return response()->json($event);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $event = Event::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    private function validateDateTimeRange(string $startDate, string $startTime, string $endDate, string $endTime): void
    {
        $start = strtotime("{$startDate} {$startTime}");
        $end = strtotime("{$endDate} {$endTime}");

        if ($start === false || $end === false || $end < $start) {
            throw ValidationException::withMessages([
                'end_time' => ['End date/time must be after or equal to start date/time.'],
            ]);
        }
    }
}

