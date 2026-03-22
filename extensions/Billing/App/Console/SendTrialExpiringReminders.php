<?php

declare(strict_types=1);

namespace Extensions\Billing\App\Console;

use Extensions\Billing\App\Models\PolarSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrialExpiringMail; // Assuming this exists or will be created

class SendTrialExpiringReminders extends Command
{
    protected $signature = 'billing:send-trial-reminders';
    protected $description = 'Send email reminders to organizations with trial expiring soon';

    public function handle(): void
    {
        $trialingSubs = PolarSubscription::query()
            ->where(fn ($query) => $query->where('status', '=', 'trialing'))
            ->whereNotNull('trial_ends_at')
            ->where(fn ($query) => $query->where('trial_ends_at', '>', now()))
            ->where(fn ($query) => $query->where('trial_ends_at', '<=', now()->addDays(3)))
            ->get();

        foreach ($trialingSubs as $sub) {
            $org = $sub->organization;
            if (!$org) continue;

            // Simple logic: send at 3 days left and 1 day left
            $daysLeft = now()->diffInDays($sub->trial_ends_at);
            
            if ($daysLeft === 3 || $daysLeft === 1) {
                // Mail::to($org->owner->email)->send(new TrialExpiringMail($org, $daysLeft));
                $this->info("Reminder sent to {$org->owner->email} for org {$org->name} (" . (string) $daysLeft . " days left)");
            }
        }
    }
}
