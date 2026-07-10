<?php

namespace App\Http\Controllers;

use App\Models\JulesDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JulesDayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $julesDays = JulesDay::query()
            ->orderBy('start')
            ->orderBy('id')
            ->get();

        return response()->json($julesDays);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->julesDayValidationRules());
        $payload = $this->normalizeJulesDayPayload($validated);
        $this->validateDateRange($payload['start'], $payload['end']);

        $julesDay = JulesDay::create([
            ...$payload,
        ]);

        return response()->json($julesDay, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $julesDay = JulesDay::query()
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate($this->julesDayValidationRules());
        $payload = $this->normalizeJulesDayPayload($validated);
        $this->validateDateRange($payload['start'], $payload['end']);

        $julesDay->update($payload);

        return response()->json($julesDay);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $julesDay = JulesDay::query()
            ->where('id', $id)
            ->firstOrFail();

        $scope = $request->input('scope', 'series');
        $occurrenceStart = $request->input('occurrence_start');

        if ($scope === 'single' && $julesDay->recurrence_type !== 'none' && is_string($occurrenceStart) && $occurrenceStart !== '') {
            $excluded = $julesDay->excluded_occurrences ?? [];
            $excluded[] = $occurrenceStart;
            $julesDay->update([
                'excluded_occurrences' => array_values(array_unique($excluded)),
            ]);

            return response()->json(['message' => 'Jules Day occurrence deleted.']);
        }

        if ($scope === 'future' && $julesDay->recurrence_type !== 'none' && is_string($occurrenceStart) && $occurrenceStart !== '') {
            $seriesStart = $julesDay->getRawOriginal('start');
            if ($occurrenceStart <= $seriesStart) {
                $julesDay->delete();

                return response()->json(['message' => 'Jules Day deleted.']);
            }

            $julesDay->update([
                'recurrence_end_type' => 'on',
                'recurrence_end_date' => $this->previousDate($occurrenceStart),
                'recurrence_occurrences' => null,
            ]);

            return response()->json(['message' => 'Jules Day series truncated.']);
        }

        $julesDay->delete();

        return response()->json(['message' => 'Jules Day deleted.']);
    }

    private function validateDateRange(string $startDate, string $endDate): void
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        if ($start === false || $end === false || $end < $start) {
            throw ValidationException::withMessages([
                'end' => ['End date must be after or equal to start date.'],
            ]);
        }
    }

    private function julesDayValidationRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            'description' => 'nullable|string|max:2000',
            'all_day' => 'nullable|boolean',
            'recurrence_type' => ['nullable', Rule::in(JulesDay::RECURRENCE_TYPES)],
            'recurrence_interval' => 'nullable|integer|min:1|max:999',
            'recurrence_unit' => ['nullable', Rule::in(JulesDay::CUSTOM_RECURRENCE_UNITS)],
            'recurrence_days_of_week' => 'nullable|array',
            'recurrence_days_of_week.*' => 'integer|min:0|max:6',
            'recurrence_end_type' => ['nullable', Rule::in(JulesDay::RECURRENCE_END_TYPES)],
            'recurrence_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start',
            'recurrence_occurrences' => 'nullable|integer|min:1|max:9999',
        ];
    }

    private function normalizeJulesDayPayload(array $validated): array
    {
        $recurrenceType = $validated['recurrence_type'] ?? 'none';
        $customInterval = (int) ($validated['recurrence_interval'] ?? 1);
        $customUnit = $validated['recurrence_unit'] ?? 'week';
        $customDays = $this->normalizeRecurrenceDays($validated['recurrence_days_of_week'] ?? []);
        $customEndType = $validated['recurrence_end_type'] ?? 'never';
        $customEndDate = $validated['recurrence_end_date'] ?? null;
        $customOccurrences = isset($validated['recurrence_occurrences'])
            ? (int) $validated['recurrence_occurrences']
            : null;

        $payload = [
            'title' => $validated['title'],
            'start' => $validated['start'],
            'end' => $validated['end'],
            'description' => $validated['description'] ?? null,
            'all_day' => true,
            'recurrence_type' => $recurrenceType,
            'recurrence_interval' => null,
            'recurrence_unit' => null,
            'recurrence_days_of_week' => null,
            'recurrence_end_type' => null,
            'recurrence_end_date' => null,
            'recurrence_occurrences' => null,
        ];

        if ($recurrenceType === 'none') {
            return $payload;
        }

        if ($recurrenceType === 'daily') {
            $payload['recurrence_interval'] = 1;
            $payload['recurrence_unit'] = 'day';
            return $payload;
        }

        if ($recurrenceType === 'weekly' || $recurrenceType === 'biweekly') {
            $eventWeekDay = (int) date('w', strtotime($payload['start']));
            $payload['recurrence_interval'] = $recurrenceType === 'weekly' ? 1 : 2;
            $payload['recurrence_unit'] = 'week';
            $payload['recurrence_days_of_week'] = [$eventWeekDay];
            return $payload;
        }

        if ($recurrenceType === 'annually') {
            $payload['recurrence_interval'] = 1;
            $payload['recurrence_unit'] = 'year';
            return $payload;
        }

        if ($recurrenceType === 'custom') {
            if ($customUnit === 'week' && count($customDays) === 0) {
                throw ValidationException::withMessages([
                    'recurrence_days_of_week' => ['Choose at least one day for weekly custom recurrence.'],
                ]);
            }

            if ($customEndType === 'on' && $customEndDate === null) {
                throw ValidationException::withMessages([
                    'recurrence_end_date' => ['An end date is required when custom recurrence ends on a date.'],
                ]);
            }

            if ($customEndType === 'after' && $customOccurrences === null) {
                throw ValidationException::withMessages([
                    'recurrence_occurrences' => ['Occurrences are required when custom recurrence ends after a count.'],
                ]);
            }

            $payload['recurrence_interval'] = $customInterval;
            $payload['recurrence_unit'] = $customUnit;
            $payload['recurrence_days_of_week'] = $customUnit === 'week' ? $customDays : null;
            $payload['recurrence_end_type'] = $customEndType;
            $payload['recurrence_end_date'] = $customEndType === 'on' ? $customEndDate : null;
            $payload['recurrence_occurrences'] = $customEndType === 'after' ? $customOccurrences : null;

            return $payload;
        }

        return $payload;
    }

    private function normalizeRecurrenceDays(array $days): array
    {
        $normalized = array_map(static fn (mixed $day) => (int) $day, $days);
        $normalized = array_values(array_unique(array_filter($normalized, static fn (int $day) => $day >= 0 && $day <= 6)));
        sort($normalized);

        return $normalized;
    }

    private function previousDate(string $date): string
    {
        return date('Y-m-d', strtotime($date . ' -1 day'));
    }
}
