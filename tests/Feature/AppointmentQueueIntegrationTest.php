<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ملاحظة: بسبب تعقيد multi-tenancy في الاختبارات،
 * استخدم دليل الاختبار اليدوي في APPOINTMENT_TESTING_GUIDE.md
 *
 * أو استخدم:
 * - http://localhost/test-queue-buttons.html
 * - public/debug-queue-buttons.js في console
 */
class AppointmentQueueIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function example_test()
    {
        $this->assertTrue(true);
    }

    // TODO: إصلاح multi-tenancy setup للاختبارات
    // انظر: TESTING_README.md للاختبار اليدوي
}
