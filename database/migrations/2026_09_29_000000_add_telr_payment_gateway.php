<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('addon_settings')) {
            return;
        }

        $values = [
            'gateway' => 'telr',
            'mode' => 'test',
            'status' => 0,
            'store_id' => '',
            'authkey' => '',
            'webhook_secret' => '',
            'panels' => 'card',
        ];

        $exists = DB::table('addon_settings')
            ->where('key_name', 'telr')
            ->where('settings_type', 'payment_config')
            ->exists();

        if (! $exists) {
            DB::table('addon_settings')->insert([
                'id' => (string) Illuminate\Support\Str::uuid(),
                'key_name' => 'telr',
                'live_values' => json_encode($values),
                'test_values' => json_encode($values),
                'settings_type' => 'payment_config',
                'mode' => 'test',
                'is_active' => 0,
                'additional_data' => json_encode(['gateway_title' => 'Telr', 'gateway_image' => '']),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('addon_settings')
            ->where('key_name', 'telr')
            ->where('settings_type', 'payment_config')
            ->delete();
    }
};
