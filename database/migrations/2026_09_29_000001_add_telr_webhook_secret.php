<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('addon_settings')
            ->where('key_name', 'telr')
            ->where('settings_type', 'payment_config')
            ->get()
            ->each(function (object $setting): void {
                foreach (['live_values', 'test_values'] as $column) {
                    $values = json_decode($setting->{$column} ?: '{}', true) ?: [];
                    if (! array_key_exists('webhook_secret', $values)) {
                        $values['webhook_secret'] = '';
                        DB::table('addon_settings')
                            ->where('id', $setting->id)
                            ->update([$column => json_encode($values)]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('addon_settings')
            ->where('key_name', 'telr')
            ->where('settings_type', 'payment_config')
            ->get()
            ->each(function (object $setting): void {
                foreach (['live_values', 'test_values'] as $column) {
                    $values = json_decode($setting->{$column} ?: '{}', true) ?: [];
                    unset($values['webhook_secret']);
                    DB::table('addon_settings')
                        ->where('id', $setting->id)
                        ->update([$column => json_encode($values)]);
                }
            });
    }
};
