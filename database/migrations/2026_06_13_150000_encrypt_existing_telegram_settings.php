<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = DB::table('site_settings')->get();

        foreach ($settings as $setting) {
            $updates = [];

            foreach (['telegram_bot_token', 'telegram_orders_chat_id', 'telegram_webhook_secret'] as $column) {
                $value = $setting->{$column};

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $updates[$column] = Crypt::encryptString($value);
            }

            if ($updates !== []) {
                DB::table('site_settings')->where('id', $setting->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        $settings = DB::table('site_settings')->get();

        foreach ($settings as $setting) {
            $updates = [];

            foreach (['telegram_bot_token', 'telegram_orders_chat_id', 'telegram_webhook_secret'] as $column) {
                $value = $setting->{$column};

                if (! is_string($value) || $value === '') {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (\Throwable) {
                    continue;
                }
            }

            if ($updates !== []) {
                DB::table('site_settings')->where('id', $setting->id)->update($updates);
            }
        }
    }
};
