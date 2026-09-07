<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class BusinessHours
{
    public const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public const LABELS = ['saturday' => 'شنبه', 'sunday' => 'یکشنبه', 'monday' => 'دوشنبه', 'tuesday' => 'سه‌شنبه', 'wednesday' => 'چهارشنبه', 'thursday' => 'پنجشنبه', 'friday' => 'جمعه'];

    public function normalize(?array $schedule): ?array
    {
        if ($schedule === null) {
            return null;
        }

        $normalized = [];
        foreach (self::DAYS as $dayIndex => $day) {
            $input = $schedule[$day] ?? ['closed' => true, 'shifts' => []];
            $closed = filter_var($input['closed'] ?? false, FILTER_VALIDATE_BOOL);
            $shifts = [];
            foreach ($closed ? [] : ($input['shifts'] ?? []) as $shift) {
                $opens = $this->validTime($shift['opens'] ?? null);
                $closes = $this->validTime($shift['closes'] ?? null);
                $nextDay = filter_var($shift['next_day'] ?? false, FILTER_VALIDATE_BOOL);
                $start = $dayIndex * 1440 + $this->minutes($opens);
                $end = $dayIndex * 1440 + $this->minutes($closes) + ($nextDay ? 1440 : 0);
                if ($end <= $start || $end - $start > 1440) {
                    throw ValidationException::withMessages(["weekly_hours.$day.shifts" => 'زمان پایان هر نوبت باید پس از شروع و حداکثر در روز بعد باشد.']);
                }
                $shifts[] = ['opens' => $opens, 'closes' => $closes, 'next_day' => $nextDay, '_start' => $start, '_end' => $end];
            }
            if (! $closed && $shifts === []) {
                throw ValidationException::withMessages(["weekly_hours.$day.shifts" => 'برای روز کاری دست‌کم یک نوبت وارد کنید یا روز را تعطیل بزنید.']);
            }
            usort($shifts, fn (array $first, array $second): int => $first['_start'] <=> $second['_start']);
            $normalized[$day] = ['closed' => $closed, 'shifts' => array_map(fn (array $shift): array => collect($shift)->except(['_start', '_end'])->all(), $shifts)];
        }

        $intervals = $this->intervals($normalized);
        foreach ($intervals as $interval) {
            $intervals[] = [$interval[0] + 10080, $interval[1] + 10080];
        }
        usort($intervals, fn (array $first, array $second): int => $first[0] <=> $second[0]);
        foreach ($intervals as $index => $interval) {
            $next = $intervals[$index + 1] ?? null;
            if ($next && $next[0] < $interval[1]) {
                throw ValidationException::withMessages(['weekly_hours' => 'نوبت‌های کاری نباید با یکدیگر هم‌پوشانی داشته باشند.']);
            }
        }

        return $normalized;
    }

    /** @return array{is_open: bool, text: string}|null */
    public function status(?array $schedule, ?CarbonImmutable $now = null): ?array
    {
        if (! $schedule) {
            return null;
        }
        $now = ($now ?? CarbonImmutable::now('Asia/Tehran'))->setTimezone('Asia/Tehran');
        $dayIndex = ($now->dayOfWeek + 1) % 7;
        $minute = $dayIndex * 1440 + $now->hour * 60 + $now->minute;
        $intervals = $this->intervals($schedule);
        $expanded = collect($intervals)->flatMap(fn (array $interval): array => [[$interval[0] - 10080, $interval[1] - 10080], $interval, [$interval[0] + 10080, $interval[1] + 10080]])->sortBy(0)->values();
        $current = $expanded->first(fn (array $interval): bool => $minute >= $interval[0] && $minute < $interval[1]);
        if ($current) {
            return ['is_open' => true, 'text' => 'باز است · تا '.$this->formatMinute($current[1])];
        }
        $next = $expanded->first(fn (array $interval): bool => $interval[0] > $minute);

        return ['is_open' => false, 'text' => $next ? 'بسته است · باز از '.$this->formatMinute($next[0]) : 'بسته است'];
    }

    /** @return array<int, array{0:int,1:int}> */
    private function intervals(array $schedule): array
    {
        $intervals = [];
        foreach (self::DAYS as $dayIndex => $day) {
            foreach ($schedule[$day]['shifts'] ?? [] as $shift) {
                $start = $dayIndex * 1440 + $this->minutes($shift['opens']);
                $end = $dayIndex * 1440 + $this->minutes($shift['closes']) + (($shift['next_day'] ?? false) ? 1440 : 0);
                $intervals[] = [$start, $end];
            }
        }

        return $intervals;
    }

    private function validTime(mixed $value): string
    {
        if (! is_string($value) || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            throw ValidationException::withMessages(['weekly_hours' => 'ساعت را با قالب معتبر وارد کنید.']);
        }

        return $value;
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }

    private function formatMinute(int $minute): string
    {
        $minute = (($minute % 10080) + 10080) % 10080;
        $day = self::DAYS[intdiv($minute, 1440)];
        $time = sprintf('%02d:%02d', intdiv($minute % 1440, 60), $minute % 60);

        return self::LABELS[$day].' '.$time;
    }
}
