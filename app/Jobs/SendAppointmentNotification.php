<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Notification;
use App\Mail\AppointmentBookedMail;
use App\Mail\AppointmentReminderMail;

class SendAppointmentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $appointment;
    public $user;
    public $type; // 'booked', 'reminder', 'cancelled', 'confirmed'
    public $locale;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment, User $user, string $type, string $locale = 'en')
    {
        $this->appointment = $appointment;
        $this->user = $user;
        $this->type = $type;
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
            switch ($this->type) {
                case 'booked':
                    Mail::to($this->user->email)
                        ->send(new AppointmentBookedMail($this->appointment, $this->user, $this->locale));
                    break;

                case 'reminder':
                    Mail::to($this->user->email)
                        ->send(new AppointmentReminderMail($this->appointment, $this->user, $this->locale));
                    break;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send email notification: ' . $e->getMessage());
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
            $provider = config('services.sms.provider', 'log');

            // Log SMS for now (can integrate real providers later)
            \Log::info('SMS Notification', [
                'to' => $phone,
                'message' => $message,
                'type' => $this->type
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send SMS notification: ' . $e->getMessage());
        }
    }

    /**
     * Prepare SMS message
     */
    private function prepareSMSMessage(): string
    {
        switch ($this->type) {
            case 'booked':
                return __('notifications.appointment_booked.sms', [
                    'name' => $this->user->name,
                    'date' => $this->appointment->date->format('Y-m-d'),
                    'time' => $this->appointment->time_slot,
                ], $this->locale);

            case 'reminder':
                return __('notifications.appointment_reminder.sms', [
                    'name' => $this->user->name,
                    'date' => $this->appointment->date->format('Y-m-d'),
                    'time' => $this->appointment->time_slot,
                ], $this->locale);

            default:
                return __('notifications.general.sms', [
                    'name' => $this->user->name,
                ], $this->locale);
        }
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
                'tenant_id' => $this->appointment->tenant_id,
                'user_id' => $this->user->id,
                'type' => 'Appointment - ' . ucfirst($this->type),
                'message' => __('notifications.appointment_' . $this->type . '.message', [
                    'date' => $this->appointment->date->format('Y-m-d'),
                    'time' => $this->appointment->time_slot,
                ], $this->locale),
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log notification: ' . $e->getMessage());
        }
    }
}
