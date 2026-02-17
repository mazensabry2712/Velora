<?php

namespace Database\Seeders;

use App\Models\WorkingDay;
use Illuminate\Database\Seeder;

class WorkingDaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            ['day_of_week' => 0, 'day_name' => 'Sunday', 'day_name_ar' => 'الأحد', 'is_active' => true],
            ['day_of_week' => 1, 'day_name' => 'Monday', 'day_name_ar' => 'الإثنين', 'is_active' => true],
            ['day_of_week' => 2, 'day_name' => 'Tuesday', 'day_name_ar' => 'الثلاثاء', 'is_active' => true],
            ['day_of_week' => 3, 'day_name' => 'Wednesday', 'day_name_ar' => 'الأربعاء', 'is_active' => true],
            ['day_of_week' => 4, 'day_name' => 'Thursday', 'day_name_ar' => 'الخميس', 'is_active' => true],
            ['day_of_week' => 5, 'day_name' => 'Friday', 'day_name_ar' => 'الجمعة', 'is_active' => false],
            ['day_of_week' => 6, 'day_name' => 'Saturday', 'day_name_ar' => 'السبت', 'is_active' => false],
        ];

        foreach ($days as $day) {
            WorkingDay::updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                $day
            );
        }

        $this->command->info('✅ Working days seeded successfully');
    }
}
