<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Store;
use App\Models\Module;
use Illuminate\Database\Seeder;

class BoxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::first();
        $module = Module::first();

        if (!$store || !$module) {
            $this->command->error('No store or module found. Please seed them first.');
            return;
        }

        $boxes = [
            [
                'store_id' => $store->id,
                'module_id' => $module->id,
                'name' => 'Pizza Surprise Box',
                'description' => 'A mysterious selection of our best pizzas.',
                'price' => 15.99,
                'item_count' => 3,
                'available_count' => 10,
                'status' => 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
            ],
            [
                'store_id' => $store->id,
                'module_id' => $module->id,
                'name' => 'Veggie Delight Box',
                'description' => 'Healthy and tasty vegetarian surprises.',
                'price' => 12.50,
                'item_count' => 4,
                'available_count' => 5,
                'status' => 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(15)->format('Y-m-d'),
            ],
            [
                'store_id' => $store->id,
                'module_id' => $module->id,
                'name' => 'Dessert Dream Box',
                'description' => 'Sweet treats to make your day.',
                'price' => 9.99,
                'item_count' => 5,
                'available_count' => 20,
                'status' => 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(7)->format('Y-m-d'),
            ]
        ];

        foreach ($boxes as $box) {
            Box::create($box);
        }

        $this->command->info('BoxSeeder completed successfully.');
    }
}
