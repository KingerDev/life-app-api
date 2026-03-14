<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:deadlines';

    protected $description = 'Send deadline reminder notifications for todos via OneSignal';

    public function handle(): void
    {
        $appId  = config('onesignal.app_id');
        $apiKey = config('onesignal.api_key');

        if (! $appId || ! $apiKey) {
            $this->error('OneSignal credentials not configured.');
            return;
        }

        // Get all user preferences with deadline notifications enabled
        $prefs = \App\Models\NotificationPreference::where('deadline_enabled', true)->get();

        $sent = 0;

        foreach ($prefs as $pref) {
            $targetDate = now()->addDays($pref->deadline_days_before)->toDateString();

            // Find todos due on target date for this user
            $todos = \App\Models\Todo::where('user_id', $pref->user_id)
                ->whereDate('due_date', $targetDate)
                ->where('is_completed', false)
                ->where('is_archived', false)
                ->get();

            if ($todos->isEmpty()) {
                continue;
            }

            $count   = $todos->count();
            $message = $count === 1
                ? "Úloha \"{$todos->first()->title}\" má zajtra termín!"
                : "Máš {$count} úlohy s termínom zajtra!";

            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id'   => $appId,
                'filters'  => [
                    ['field' => 'external_user_id', 'value' => $pref->user_id],
                ],
                'headings' => ['en' => 'Life App – Deadline'],
                'contents' => ['en' => $message],
                'url'      => config('app.url') . '/todos',
            ]);

            $sent++;
        }

        $this->info("Sent deadline reminders to {$sent} users.");
    }
}
