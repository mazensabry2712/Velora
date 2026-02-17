<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Queue;
use App\Models\User;
use App\Models\Notification;
use App\Mail\QueueUpdateMail;

class SendQueueNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue;
    public $user;
    public $updateType; // 'next', 'position_update', 'ready', 'skipped'
    public $locale;

    /**
     * Create a new job instance.
     */
    public function __construct(Queue $queue, User $user, string $updateType, string $locale = 'en')
    {
        $this->queue = $queue;
        $this->user = $user;
        $this->updateType = $updateType;
        $this->locale = $locale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send Email
        $this->sendEmail();

        // Send SMS (if enabled)
        if ($this->shouldSendSMS()) {
            $this->sendSMS();
        }

        // Log notification in database
        $this->logNotification();
    }

    /**
     * Send email notification
     */
    private function sendEmail(): void
    {
        try {
            Mail::to($this->user->email)
                ->send(new QueueUpdateMail($this->queue, $this->user, $this->updateType, $this->locale));
        } catch (\Exception $e) {
            \Log::error('Failed to send queue email notification: ' . $e->getMessage());
        }
    }

    /**
     * Send SMS notification
     */
    private function sendSMS(): void
    {
        try {
            $tenant = tenant();
            $settings = $tenant->settings;

            if (!$settings || !($settings->notification_settings['sms'] ?? false)) {
                return;
            }

            $phone = $this->user->phone ?? null;
            if (!$phone) {
                return;
            }

            $message = $this->prepareSMSMessage();

            \Log::info('Queue SMS Notification', [
                'to' => $phone,
                'message' => $message,
                'type' => $this->updateType
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send queue SMS notification: ' . $e->getMessage());
        }
    }

    /**
     * Prepare SMS message
     */
    private function prepareSMSMessage(): string
    {
        switch ($this->updateType) {
            case 'next':
                return __('notifications.queue_update.next_sms', [
                    'name' => $this->user->name,
                    'queue_number' => $this->queue->queue_number,
                ], $this->locale);

            case 'ready':
                return __('notifications.queue_update.ready_sms', [
                    'name' => $this->user->name,
                    'queue_number' => $this->queue->queue_number,
                    'position' => $this->calculatePosition(),
                ], $this->locale);

            case 'position_update':
                return __('notifications.queue_update.position_sms', [
                    'name' => $this->user->name,
                    'position' => $this->calculatePosition(),
                    'estimated_time' => $this->queue->estimated_wait_time,
                ], $this->locale);

            default:
                return __('notifications.queue_update.general_sms', [
                    'name' => $this->user->name,
                ], $this->locale);
        }
    }

    /**
     * Calculate queue position
     */
    private function calculatePosition(): int
    {
        return Queue::where('tenant_id', $this->queue->tenant_id)
            ->where('status', 'Waiting')
            ->whereDate('created_at', now()->toDateString())
            ->where(function ($query) {
                $query->where('priority', '>', $this->queue->priority)
                    ->orWhere(function ($q) {
                        $q->where('priority', $this->queue->priority)
                            ->where('queue_number', '<', $this->queue->queue_number);
                    });
            })
            ->count() + 1;
    }

    /**
     * Check if SMS should be sent
     */
    private function shouldSendSMS(): bool
    {
        $tenant = tenant();
        $settings = $tenant->settings ?? null;

        return $settings && ($settings->notification_settings['sms'] ?? false);
    }

    /**
     * Log notification in database
     */
    private function logNotification(): void
    {
        try {
            Notification::create([
                'tenant_id' => $this->queue->tenant_id,
                'user_id' => $this->user->id,
                'type' => 'Queue - ' . ucfirst($this->updateType),
                'message' => __('notifications.queue_update.message', [
                    'queue_number' => $this->queue->queue_number,
                    'status' => $this->updateType,
                ], $this->locale),
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log queue notification: ' . $e->getMessage());
        }
    }
}
