<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SanitizeStaging extends Command
{
    protected $signature = 'staging:sanitize';

    protected $description = 'Clear push tokens, mask customer phones, and disable live SMS. Refuses to run unless APP_ENV=staging.';

    public function handle(): int
    {
        if (! app()->environment('staging')) {
            $this->error('Refusing to run: APP_ENV must be staging.');

            return 1;
        }

        $this->nullColumn('users', 'cm_firebase_token');
        $this->nullColumn('delivery_men', 'fcm_token');
        $this->nullColumn('vendors', 'firebase_token');
        $this->nullColumn('guests', 'fcm_token');

        $this->maskPhone('users');
        $this->maskPhone('delivery_men');

        if (Schema::hasTable('addon_settings')) {
            $rows = DB::table('addon_settings')->where('settings_type', 'sms_config')->get();
            foreach ($rows as $row) {
                $live = json_decode($row->live_values ?? '', true);
                if (! is_array($live)) {
                    $live = [];
                }
                $live['status'] = '0';
                DB::table('addon_settings')->where('id', $row->id)->update([
                    'is_active' => 0,
                    'live_values' => json_encode($live),
                ]);
            }
            $this->info('Disabled '.$rows->count().' SMS gateway row(s).');
        }

        if (Schema::hasTable('business_settings') && Schema::hasColumn('business_settings', 'value')) {
            $mail = DB::table('business_settings')->where('key', 'mail_config')->first();
            if ($mail) {
                $config = json_decode($mail->value ?? '', true);
                if (is_array($config)) {
                    $config['status'] = 0;
                    DB::table('business_settings')->where('key', 'mail_config')->update([
                        'value' => json_encode($config),
                    ]);
                    $this->info('Disabled mail_config.');
                }
            }
        }

        $this->info('Staging data sanitized.');

        return 0;
    }

    private function nullColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $count = DB::table($table)->whereNotNull($column)->update([$column => null]);
        $this->info("Cleared {$table}.{$column} on {$count} row(s).");
    }

    private function maskPhone(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'phone') || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        DB::table($table)->update([
            'phone' => DB::raw("CONCAT('010', LPAD(id, 8, '0'))"),
        ]);
        $this->info("Masked {$table}.phone.");
    }
}
