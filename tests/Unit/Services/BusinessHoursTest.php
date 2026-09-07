<?php

namespace Tests\Unit\Services;

use App\Services\BusinessHours;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessHoursTest extends TestCase
{
    public function test_it_normalizes_multiple_shifts_and_reports_live_status_in_tehran(): void
    {
        $hours = app(BusinessHours::class);
        $schedule = $hours->normalize([
            'saturday' => ['closed' => false, 'shifts' => [
                ['opens' => '18:00', 'closes' => '22:00', 'next_day' => false],
                ['opens' => '09:00', 'closes' => '13:00', 'next_day' => false],
            ]],
        ]);

        $this->assertSame('09:00', $schedule['saturday']['shifts'][0]['opens']);
        $this->assertTrue($schedule['friday']['closed']);
        $this->assertSame(
            ['is_open' => true, 'text' => 'باز است · تا شنبه 13:00'],
            $hours->status($schedule, CarbonImmutable::parse('2026-09-05 10:30:00', 'Asia/Tehran')),
        );
    }

    public function test_it_rejects_overlap_across_the_week_boundary(): void
    {
        $this->expectException(ValidationException::class);

        app(BusinessHours::class)->normalize([
            'friday' => ['closed' => false, 'shifts' => [['opens' => '23:00', 'closes' => '02:00', 'next_day' => true]]],
            'saturday' => ['closed' => false, 'shifts' => [['opens' => '01:00', 'closes' => '04:00', 'next_day' => false]]],
        ]);
    }
}
