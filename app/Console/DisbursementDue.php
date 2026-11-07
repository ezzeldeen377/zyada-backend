<?php

namespace App\Console;

use Carbon\CarbonInterface;

class DisbursementDue
{
    /**
     * True only in the single minute configured in business settings.
     * Monthly runs on the 28th, once, so a disbursement is not created on
     * each of the 28th through the 31st.
     */
    public static function isDue(?string $type, ?string $period, ?string $weekDay, ?string $time, CarbonInterface $now): bool
    {
        if ($type !== 'automated') {
            return false;
        }

        $parts = explode(':', (string) $time);
        if (count($parts) < 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return false;
        }

        $hour = (int) $parts[0];
        $minute = (int) $parts[1];
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return false;
        }

        if ((int) $now->format('G') !== $hour || (int) $now->format('i') !== $minute) {
            return false;
        }

        $period = $period ?: 'daily';

        if ($period === 'daily') {
            return true;
        }

        if ($period === 'weekly') {
            return strtolower($now->format('l')) === strtolower((string) $weekDay);
        }

        if ($period === 'monthly') {
            return (int) $now->format('j') === 28;
        }

        return false;
    }
}
