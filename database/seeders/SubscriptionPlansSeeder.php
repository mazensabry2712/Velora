<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for small businesses starting out',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'max_users' => 5,
                'max_appointments' => 100,
                'storage_limit' => 1024, // 1GB
                'features' => [
                    'Appointment Management',
                    'Basic Queue System',
                    'Email Notifications',
                    '5 Staff Users',
                    '1GB Storage',
                ],
                'is_active' => true,
                'is_popular' => false,
                'trial_days' => 14,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing businesses with more needs',
                'price' => 79.99,
                'billing_cycle' => 'monthly',
                'max_users' => 20,
                'max_appointments' => 500,
                'storage_limit' => 5120, // 5GB
                'features' => [
                    'All Basic Features',
                    'Advanced Queue Management',
                    'SMS Notifications',
                    'Custom Branding',
                    'Priority Support',
                    '20 Staff Users',
                    '5GB Storage',
                    'Reports & Analytics',
                ],
                'is_active' => true,
                'is_popular' => true,
                'trial_days' => 14,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations with advanced requirements',
                'price' => 199.99,
                'billing_cycle' => 'monthly',
                'max_users' => null, // Unlimited
                'max_appointments' => null, // Unlimited
                'storage_limit' => null, // Unlimited
                'features' => [
                    'All Professional Features',
                    'Unlimited Users',
                    'Unlimited Appointments',
                    'Unlimited Storage',
                    'API Access',
                    'Custom Integrations',
                    'Dedicated Support',
                    'SLA Guarantee',
                    'White Label Option',
                ],
                'is_active' => true,
                'is_popular' => false,
                'trial_days' => 30,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('✅ Subscription plans seeded successfully!');
    }
}
