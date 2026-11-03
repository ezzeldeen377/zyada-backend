<?php

namespace Tests\Unit;

use App\Console\DisbursementDue;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DisbursementDueTest extends TestCase
{
    /** @test */
    public function manual_mode_never_runs(): void
    {
        $now = Carbon::parse('2026-11-03 12:00:00', 'UTC');

        $this->assertFalse(DisbursementDue::isDue('manual', 'daily', null, '12:00', $now));
    }

    /** @test */
    public function daily_runs_only_in_the_configured_minute(): void
    {
        $due = Carbon::parse('2026-11-03 09:15:00', 'UTC');
        $later = Carbon::parse('2026-11-03 09:16:00', 'UTC');

        $this->assertTrue(DisbursementDue::isDue('automated', 'daily', null, '09:15', $due));
        $this->assertFalse(DisbursementDue::isDue('automated', 'daily', null, '09:15', $later));
    }

    /** @test */
    public function weekly_runs_only_on_the_configured_weekday(): void
    {
        $tuesday = Carbon::parse('2026-11-03 09:15:00', 'UTC');
        $wednesday = Carbon::parse('2026-11-04 09:15:00', 'UTC');

        $this->assertTrue(DisbursementDue::isDue('automated', 'weekly', 'tuesday', '09:15', $tuesday));
        $this->assertFalse(DisbursementDue::isDue('automated', 'weekly', 'tuesday', '09:15', $wednesday));
    }

    /** @test */
    public function monthly_runs_only_on_the_28th(): void
    {
        $day28 = Carbon::parse('2026-11-28 09:15:00', 'UTC');
        $day29 = Carbon::parse('2026-11-29 09:15:00', 'UTC');

        $this->assertTrue(DisbursementDue::isDue('automated', 'monthly', null, '09:15', $day28));
        $this->assertFalse(DisbursementDue::isDue('automated', 'monthly', null, '09:15', $day29));
    }
}
