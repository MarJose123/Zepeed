<?php

namespace Tests\Unit\Concerns;

use App\Concerns\TranslatesDateFormat;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class TranslatesDateFormatTest extends TestCase
{
    private function formatter(): object
    {
        return new class
        {
            use TranslatesDateFormat;

            public function call(CarbonImmutable $date, string $format): string
            {
                return $this->formatDate($date, $format);
            }
        };
    }

    public function testHourMinuteFormat(): void
    {
        $date = CarbonImmutable::create(2026, 6, 30, 14, 5, 9);

        $this->assertSame('14:05', $this->formatter()->call($date, '%H:%i'));
    }

    public function testMonthDayFormat(): void
    {
        $date = CarbonImmutable::create(2026, 6, 9, 0, 0, 0);

        $this->assertSame('06/09', $this->formatter()->call($date, '%m/%d'));
    }

    public function testMonthYearFormat(): void
    {
        $date = CarbonImmutable::create(2025, 3, 1, 0, 0, 0);

        $this->assertSame('03/2025', $this->formatter()->call($date, '%m/%Y'));
    }

    public function testMonthDayHourMinuteFormat(): void
    {
        $date = CarbonImmutable::create(2026, 12, 25, 9, 30, 0);

        $this->assertSame('12/25 09:30', $this->formatter()->call($date, '%m/%d %H:%i'));
    }

    public function testHourBucketFormatTruncatesMinutesAndSeconds(): void
    {
        $date = CarbonImmutable::create(2026, 6, 30, 14, 37, 22);

        $this->assertSame('2026-06-30 14:00:00', $this->formatter()->call($date, '%Y-%m-%d %H:00:00'));
    }

    public function testMinuteBucketFormatTruncatesSeconds(): void
    {
        $date = CarbonImmutable::create(2026, 6, 30, 14, 37, 22);

        $this->assertSame('2026-06-30 14:37:00', $this->formatter()->call($date, '%Y-%m-%d %H:%i:00'));
    }
}
