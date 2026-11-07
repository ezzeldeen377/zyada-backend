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

        $this->updateTelrValues(static function (array $values): array {
            if (! array_key_exists('mobile_api_key', $values)) {
                $values['mobile_api_key'] = '';
            }

            return $values;
        });
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('addon_settings')) {
            return;
        }

        $this->updateTelrValues(static function (array $values): array {
            unset($values['mobile_api_key']);

            return $values;
        });
    }

    private function updateTelrValues(callable $transform): void
    {
        $setting = DB::table('addon_settings')
            ->where('key_name', 'telr')
            ->where('settings_type', 'payment_config')
            ->first();

        if (! $setting) {
            return;
        }

        $update = [];
        foreach (['live_values', 'test_values'] as $column) {
            $values = json_decode((string) ($setting->$column ?? '{}'), true);
            if (! is_array($values)) {
                continue;
            }

            $update[$column] = json_encode($transform($values));
        }

        if ($update !== []) {
            $update['updated_at'] = now();
            DB::table('addon_settings')->where('id', $setting->id)->update($update);
        }
    }
};
