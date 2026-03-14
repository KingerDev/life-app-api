<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendWeeklyWheelReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:weekly-wheel';

    protected $description = 'Send weekly Wheel of Life reminder notifications via OneSignal';

    public function handle(): void
    {
        $appId  = config('onesignal.app_id');
        $apiKey = config('onesignal.api_key');

        if (! $appId || ! $apiKey) {
            $this->error('OneSignal credentials not configured.');
            return;
        }

        $prefs = \App\Models\NotificationPreference::where('weekly_wheel_enabled', true)->get();

        foreach ($prefs as $pref) {
            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id'   => $appId,
                'filters'  => [
                    ['field' => 'external_user_id', 'value' => $pref->user_id],
                ],
                'headings' => ['en' => 'Life App'],
                'contents' => ['en' => 'Čas hodnotiť tento týždeň – otvor Koleso života 🎯'],
                'url'      => config('app.url') . '/wheel/create',
            ]);
        }

        $this->info("Sent weekly wheel reminders to {$prefs->count()} users.");
    }
}
