<?php

namespace App\Http\Controllers;

use App\Models\JulesDay;
use Carbon\Carbon;
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
        $payload = $this->normalizePayload($validated);

        $julesDay = JulesDay::create($payload);

        return response()->json($julesDay, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $julesDay = JulesDay::query()->where('id', $id)->firstOrFail();

        $validated = $request->validate($this->julesDayValidationRules());
        $payload = $this->normalizePayload($validated);

        $julesDay->update($payload);

        return response()->json($julesDay->refresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $julesDay = JulesDay::query()->where('id', $id)->firstOrFail();

        $scope = $request->input('scope', 'series');
        $occurrenceStart = $request->input('occurrence_start');

        if ($scope === 'single' && ($julesDay->recurrence_type ?? 'none') !== 'none' && is_string($occurrenceStart) && $occurrenceStart !== '') {
            $excluded = $julesDay->excluded_occurrences ?? [];
            $excluded[] = $occurrenceStart;
            $julesDay->update(['excluded_occurrences' => array_values(array_unique($excluded))]);
            return response()->json(['message' => 'Jules Day occurrence deleted.']);
        }

        if ($scope === 'future' && ($julesDay->recurrence_type ?? 'none') !== 'none' && is_string($occurrenceStart) && $occurrenceStart !== '') {
            $seriesStart = $julesDay->getRawOriginal('start');
            if ($occurrenceStart <= $seriesStart) {
                $julesDay->delete();
                return response()->json(['message' => 'Jules Day deleted.']);
            }
            $julesDay->update([
                'recurrence_end_type' => 'on',
                'recurrence_end_date' => date('Y-m-d', strtotime($occurrenceStart . ' -1 day')),
                'recurrence_occurrences' => null,
            ]);
            return response()->json(['message' => 'Jules Day series truncated.']);
        }

        $julesDay->delete();

        return response()->json(['message' => 'Jules Day deleted.']);
    }

    private function julesDayValidationRules(): array
    {
        return [
            'type' => ['required', Rule::in(JulesDay::TYPES)],
            'start' => 'required|date_format:Y-m-d',
            'coming_time' => 'nullable|date_format:H:i',
            'leaving_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string|max:2000',
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

    private function normalizePayload(array $validated): array
    {
        $recurrenceType = $validated['recurrence_type'] ?? 'none';

        $payload = [
            'type' => $validated['type'],
            'start' => $validated['start'],
            'end' => $validated['start'],
            'coming_time' => $validated['coming_time'] ?? null,
            'leaving_time' => $validated['leaving_time'] ?? null,
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
            $payload['recurrence_interval'] = $recurrenceType === 'weekly' ? 1 : 2;
            $payload['recurrence_unit'] = 'week';
            $payload['recurrence_days_of_week'] = [(int) date('w', strtotime($validated['start']))];
            return $payload;
        }

        if ($recurrenceType === 'annually') {
            $payload['recurrence_interval'] = 1;
            $payload['recurrence_unit'] = 'year';
            return $payload;
        }

        if ($recurrenceType === 'custom') {
            $customUnit = $validated['recurrence_unit'] ?? 'week';
            $customDays = $this->normalizeRecurrenceDays($validated['recurrence_days_of_week'] ?? []);
            $customEndType = $validated['recurrence_end_type'] ?? 'never';

            if ($customUnit === 'week' && count($customDays) === 0) {
                throw ValidationException::withMessages([
                    'recurrence_days_of_week' => ['Choose at least one day for weekly custom recurrence.'],
                ]);
            }
            if ($customEndType === 'on' && empty($validated['recurrence_end_date'])) {
                throw ValidationException::withMessages([
                    'recurrence_end_date' => ['An end date is required when custom recurrence ends on a date.'],
                ]);
            }
            if ($customEndType === 'after' && empty($validated['recurrence_occurrences'])) {
                throw ValidationException::withMessages([
                    'recurrence_occurrences' => ['Occurrences are required when custom recurrence ends after a count.'],
                ]);
            }

            $payload['recurrence_interval'] = (int) ($validated['recurrence_interval'] ?? 1);
            $payload['recurrence_unit'] = $customUnit;
            $payload['recurrence_days_of_week'] = $customUnit === 'week' ? $customDays : null;
            $payload['recurrence_end_type'] = $customEndType;
            $payload['recurrence_end_date'] = $customEndType === 'on' ? $validated['recurrence_end_date'] : null;
            $payload['recurrence_occurrences'] = $customEndType === 'after' ? (int) $validated['recurrence_occurrences'] : null;
        }

        return $payload;
    }

    private function normalizeRecurrenceDays(array $days): array
    {
        $normalized = array_map(static fn (mixed $d) => (int) $d, $days);
        $normalized = array_values(array_unique(array_filter($normalized, static fn (int $d) => $d >= 0 && $d <= 6)));
        sort($normalized);
        return $normalized;
    }

    private function replaceOverlappingJulesDays(JulesDay $julesDay): void
    {
        $targetDate = $this->resolveOverlapTargetDate($julesDay);
        $occupiedDates = $this->getOccupiedDatesUpTo($julesDay, $targetDate);
        $occupiedDateSet = array_fill_keys($occupiedDates, true);

        if ($occupiedDates === []) {
            return;
        }

        $existingDays = JulesDay::query()
            ->where('id', '<>', $julesDay->id)
            ->orderBy('start')
            ->orderBy('id')
            ->get();

        foreach ($existingDays as $existingDay) {
            if (($existingDay->recurrence_type ?? 'none') === 'none') {
                $segments = $this->subtractDateRange($existingDay->getRawOriginal('start'), $existingDay->getRawOriginal('end'), $occupiedDateSet);

                if ($segments === []) {
                    $existingDay->delete();
                    continue;
                }

                $firstSegment = array_shift($segments);
                $existingDay->update([
                    'start' => $firstSegment['start'],
                    'end' => $firstSegment['end'],
                    'description' => $existingDay->description,
                    'all_day' => true,
                    'recurrence_type' => 'none',
                    'recurrence_interval' => null,
                    'recurrence_unit' => null,
                    'recurrence_days_of_week' => null,
                    'recurrence_end_type' => null,
                    'recurrence_end_date' => null,
                    'recurrence_occurrences' => null,
                    'excluded_occurrences' => null,
                ]);

                foreach ($segments as $segment) {
                    JulesDay::create($this->replacementPayloadFromSource($existingDay, $segment['start'], $segment['end']));
                }

                continue;
            }

            $excludedOccurrences = $existingDay->excluded_occurrences ?? [];
            $occurrences = $this->getOccurrenceIntervalsUpTo($existingDay, $targetDate, $excludedOccurrences);

            foreach ($occurrences as $occurrence) {
                $overlappingDates = [];
                $cursor = $occurrence['start'];
                while ($cursor <= $occurrence['end']) {
                    if (isset($occupiedDateSet[$cursor])) {
                        $overlappingDates[] = $cursor;
                    }
                    $cursor = Carbon::parse($cursor)->addDay()->format('Y-m-d');
                }

                if ($overlappingDates === []) {
                    continue;
                }

                $excludedOccurrences[] = $occurrence['start'];
                $residualSegments = $this->subtractDateRange($occurrence['start'], $occurrence['end'], array_fill_keys($overlappingDates, true));

                foreach ($residualSegments as $segment) {
                    JulesDay::create($this->replacementPayloadFromSource($existingDay, $segment['start'], $segment['end']));
                }
            }

            $existingDay->update([
                'excluded_occurrences' => array_values(array_unique($excludedOccurrences)),
            ]);
        }
    }

    private function resolveOverlapTargetDate(JulesDay $julesDay): string
    {
        $dates = [$julesDay->end];
        $latestStoredEnd = JulesDay::query()
            ->where('id', '<>', $julesDay->id)
            ->max('end');

        if (is_string($latestStoredEnd) && $latestStoredEnd !== '') {
            $dates[] = $latestStoredEnd;
        }

        $dates[] = Carbon::parse($julesDay->start)->addYears(5)->format('Y-m-d');

        return max($dates);
    }

    private function hasRemainingOccurrences(JulesDay $julesDay, array $excludedOccurrences, string $targetDate): bool
    {
        if (($julesDay->recurrence_type ?? 'none') === 'none') {
            return ! in_array($julesDay->getRawOriginal('start'), $excludedOccurrences, true);
        }

        return $this->getOccupiedDatesUpTo($julesDay, $targetDate, $excludedOccurrences) !== [];
    }

    /**
     * @param  array<string, true>  $blockedDates
     * @return array<int, array{start: string, end: string}>
     */
    private function subtractDateRange(string $startDate, string $endDate, array $blockedDates): array
    {
        $segments = [];
        $currentStart = null;
        $cursor = $startDate;

        while ($cursor <= $endDate) {
            $isBlocked = isset($blockedDates[$cursor]);

            if (! $isBlocked && $currentStart === null) {
                $currentStart = $cursor;
            }

            if (($isBlocked || $cursor === $endDate) && $currentStart !== null) {
                $segmentEnd = $isBlocked ? $this->previousDate($cursor) : $cursor;
                if ($currentStart <= $segmentEnd) {
                    $segments[] = ['start' => $currentStart, 'end' => $segmentEnd];
                }
                $currentStart = $isBlocked ? null : null;
            }

            $cursor = Carbon::parse($cursor)->addDay()->format('Y-m-d');
        }

        return $segments;
    }

    /**
     * @param  array<int, string>  $excludedOccurrences
     * @return array<int, array{start: string, end: string}>
     */
    private function getOccurrenceIntervalsUpTo(JulesDay $julesDay, string $targetDate, array $excludedOccurrences = []): array
    {
        $starts = $this->getOccurrenceStartDatesUpTo($julesDay, $targetDate, $excludedOccurrences);
        $duration = max(1, $this->diffDays($julesDay->getRawOriginal('start'), $julesDay->getRawOriginal('end')) + 1);

        return array_map(static function (string $startDate) use ($duration) {
            return [
                'start' => $startDate,
                'end' => Carbon::parse($startDate)->addDays($duration - 1)->format('Y-m-d'),
            ];
        }, $starts);
    }

    /**
     * @return array{title: string, start: string, end: string, description: ?string, all_day: bool, recurrence_type: string, recurrence_interval: null, recurrence_unit: null, recurrence_days_of_week: null, recurrence_end_type: null, recurrence_end_date: null, recurrence_occurrences: null, excluded_occurrences: null}
     */
    private function replacementPayloadFromSource(JulesDay $source, string $startDate, string $endDate): array
    {
        return [
            'title' => $source->title,
            'start' => $startDate,
            'end' => $endDate,
            'transition_time' => $source->transition_time,
            'coming_time' => $source->coming_time,
            'leaving_time' => $source->leaving_time,
            'description' => $source->description,
            'all_day' => true,
            'recurrence_type' => 'none',
            'recurrence_interval' => null,
            'recurrence_unit' => null,
            'recurrence_days_of_week' => null,
            'recurrence_end_type' => null,
            'recurrence_end_date' => null,
            'recurrence_occurrences' => null,
            'excluded_occurrences' => null,
        ];
    }

    /**
     * @param  array<int, string>  $excludedOccurrences
     * @return array<int, string>
     */
    private function getOccupiedDatesUpTo(JulesDay $julesDay, string $targetDate, array $excludedOccurrences = []): array
    {
        $starts = $this->getOccurrenceStartDatesUpTo($julesDay, $targetDate, $excludedOccurrences);
        $duration = max(1, $this->diffDays($julesDay->getRawOriginal('start'), $julesDay->getRawOriginal('end')) + 1);
        $dates = [];

        foreach ($starts as $startDate) {
            $endDate = Carbon::parse($startDate)->addDays($duration - 1)->format('Y-m-d');
            $cappedEnd = $endDate > $targetDate ? $targetDate : $endDate;
            $cursor = $startDate;

            while ($cursor <= $cappedEnd) {
                $dates[] = $cursor;
                $cursor = Carbon::parse($cursor)->addDay()->format('Y-m-d');
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * @param  array<int, string>  $excludedOccurrences
     * @return array<int, string>
     */
    private function getOccurrenceStartDatesUpTo(JulesDay $julesDay, string $targetDate, array $excludedOccurrences = []): array
    {
        $type = $julesDay->recurrence_type ?? 'none';
        $starts = [];
        $firstStart = $julesDay->getRawOriginal('start');
        $excludedStarts = array_fill_keys($excludedOccurrences, true);
        $generatedCount = 0;

        if ($type === 'none') {
            if ($firstStart <= $targetDate && ! isset($excludedStarts[$firstStart])) {
                $starts[] = $firstStart;
            }

            return $starts;
        }

        $interval = max(1, (int) ($julesDay->recurrence_interval ?? 1));
        $unit = $julesDay->recurrence_unit ?: ($type === 'daily' ? 'day' : ($type === 'annually' ? 'year' : 'week'));
        $daysOfWeek = ! empty($julesDay->recurrence_days_of_week)
            ? $julesDay->recurrence_days_of_week
            : [Carbon::parse($firstStart)->dayOfWeek];
        $endType = $julesDay->recurrence_end_type ?? 'never';
        $endDate = $julesDay->recurrence_end_date ? Carbon::parse($julesDay->recurrence_end_date)->format('Y-m-d') : null;
        $maxOccurrences = $endType === 'after' ? (int) ($julesDay->recurrence_occurrences ?? 0) : null;

        $pushIfValid = function (string $candidate) use (&$starts, &$generatedCount, $targetDate, $endType, $endDate, $maxOccurrences, $excludedStarts): bool {
            if ($candidate > $targetDate) {
                return false;
            }

            if ($endType === 'on' && is_string($endDate) && $candidate > $endDate) {
                return false;
            }

            if ($maxOccurrences !== null && $generatedCount >= $maxOccurrences) {
                return false;
            }

            $generatedCount += 1;

            if (! isset($excludedStarts[$candidate])) {
                $starts[] = $candidate;
            }

            return true;
        };

        if ($unit === 'week') {
            $cursor = $firstStart;
            $safety = 0;

            while ($cursor <= $targetDate && $safety < 10000) {
                $dayDiff = $this->diffDays($firstStart, $cursor);
                if ($dayDiff >= 0) {
                    $weekOffset = intdiv($dayDiff, 7);
                    $weekday = Carbon::parse($cursor)->dayOfWeek;

                    if ($weekOffset % $interval === 0 && in_array($weekday, $daysOfWeek, true)) {
                        if (! $pushIfValid($cursor)) {
                            break;
                        }
                    }
                }

                if ($endType === 'on' && is_string($endDate) && $cursor > $endDate) {
                    break;
                }

                $cursor = Carbon::parse($cursor)->addDay()->format('Y-m-d');
                $safety += 1;
            }

            return $starts;
        }

        $candidate = $firstStart;
        $safety = 0;

        while ($candidate <= $targetDate && $safety < 5000) {
            if (! $pushIfValid($candidate)) {
                break;
            }

            if ($maxOccurrences !== null && count($starts) >= $maxOccurrences) {
                break;
            }

            if ($unit === 'day') {
                $candidate = Carbon::parse($candidate)->addDays($interval)->format('Y-m-d');
            } elseif ($unit === 'month') {
                $candidate = Carbon::parse($candidate)->addMonths($interval)->format('Y-m-d');
            } else {
                $candidate = Carbon::parse($candidate)->addYears($interval)->format('Y-m-d');
            }

            $safety += 1;
        }

        return $starts;
    }

    private function julesDayOccursOnDate(JulesDay $julesDay, string $date): bool
    {
        $starts = $this->getOccurrenceStartDatesUpTo($julesDay, $date);
        $duration = max(1, $this->diffDays($julesDay->getRawOriginal('start'), $julesDay->getRawOriginal('end')) + 1);

        foreach ($starts as $startDate) {
            $endDate = Carbon::parse($startDate)->addDays($duration - 1)->format('Y-m-d');
            if ($startDate <= $date && $date <= $endDate) {
                return true;
            }
        }

        return false;
    }

    private function diffDays(string $startDate, string $endDate): int
    {
        return Carbon::parse($startDate)->startOfDay()->diffInDays(Carbon::parse($endDate)->startOfDay());
    }
}
