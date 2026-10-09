<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DefaultStoreScheduleTest extends TestCase
{
    /** @test */
    public function every_new_store_persistence_path_creates_the_default_schedule(): void
    {
        $root = dirname(__DIR__, 2).'/';

        foreach ([
            'app/Http/Controllers/Admin/VendorController.php' => 5,
            'app/Http/Controllers/VendorController.php' => 1,
            'app/Http/Controllers/Api/V1/Auth/VendorLoginController.php' => 1,
        ] as $path => $expectedCalls) {
            $contents = file_get_contents($root.$path);

            $this->assertSame(
                $expectedCalls,
                substr_count($contents, 'StoreLogic::insert_schedule('),
                "{$path} must initialize schedules for every new store path."
            );
        }

        foreach ([
            'app/Http/Controllers/Admin/VendorController.php',
            'app/Http/Controllers/VendorController.php',
            'app/Http/Controllers/Api/V1/Auth/VendorLoginController.php',
        ] as $path) {
            $this->assertStringNotContainsString("['always_open']", file_get_contents($root.$path));
        }
    }

    /** @test */
    public function the_default_schedule_covers_all_days_for_24_hours(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/app/CentralLogics/StoreLogic.php');

        $this->assertStringContainsString('array $days=[0,1,2,3,4,5,6]', $contents);
        $this->assertStringContainsString("String \$opening_time='00:00:00'", $contents);
        $this->assertStringContainsString("String \$closing_time='23:59:59'", $contents);
    }
}
