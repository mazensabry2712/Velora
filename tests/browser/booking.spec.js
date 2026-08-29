import { test, expect } from '@playwright/test';

const bookingUrl = '/book';

const mockServices = {
    success: true,
    data: [{ id: 101, name: 'General Consultation', duration_minutes: 30 }],
};

const mockStaff = {
    success: true,
    data: [{ id: 202, name: 'Dr. Browser Test' }],
};

const mockSlots = {
    success: true,
    data: [
        { start_time: '10:00:00', label: '10:00 AM' },
        { start_time: '10:30:00', label: '10:30 AM' },
    ],
};

async function installDeterministicBookingMocks(page, { mockSubmit = false } = {}) {
    await page.route('**/api/booking/services', async (route) => {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockServices) });
    });
    await page.route('**/api/booking/staff/by-service/*', async (route) => {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockStaff) });
    });
    await page.route('**/api/booking/available-timeslots?**', async (route) => {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockSlots) });
    });
    if (mockSubmit) {
        await page.route('**/api/appointments', async (route) => {
            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, data: { appointment: { public_reference: 'VL-BROWSER01' } } }),
            });
        });
    }
}

async function completeDeterministicBooking(page) {
    await expect(page.locator('#serviceCards .booking-choice').first()).toBeVisible({ timeout: 5000 });
    await page.locator('#serviceCards .booking-choice').first().click();
    await expect(page.locator('#staffCards .booking-choice').first()).toBeVisible({ timeout: 5000 });
    await page.locator('#staffCards .booking-choice').first().click();
    await page.locator('#dateChoices .booking-date').first().click();
    await expect(page.locator('#timeOptions .booking-slot').first()).toBeVisible({ timeout: 5000 });
    await page.locator('#timeOptions .booking-slot').first().click();
    await page.locator('#name').fill('Browser Test Customer');
    await page.locator('#phone').fill('01000000000');
    await page.locator('#email').fill(`browser-test-${Date.now()}@example.test`);
}

test.describe('Velora public booking V3', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });
    });

    test('renders tenant branding and clean booking surface', async ({ page }) => {
        await expect(page.locator('.booking-header')).toBeVisible();
        await expect(page.locator('.booking-logo')).toBeVisible();
        await expect(page.locator('.booking-hero h1')).toBeVisible();
        await expect(page.locator('#bookingForm')).toBeVisible();
        await expect(page.locator('.booking-summary')).toBeVisible();
        await expect(page.locator('#successMessage')).toHaveCount(0);
        await expect(page.locator('[class*="vb2-"]')).toHaveCount(0);
        await expect(page.locator('[class*="vb-final"]')).toHaveCount(0);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    });

    test('mobile controls remain touch friendly and fit the viewport', async ({ page }) => {
        const controls = await page.locator('.booking-controls button, .booking-choice:visible, .booking-date:visible, .booking-slot:visible, .booking-btn:visible').evaluateAll((items) =>
            items.filter((item) => {
                const rect = item.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0;
            }).map((item) => item.getBoundingClientRect().height),
        );
        expect(controls.length).toBeGreaterThan(0);
        expect(controls.every((height) => height >= 44)).toBe(true);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    });

    test('renders only configured tenant languages', async ({ page }) => {
        const select = page.locator('.booking-language select');
        await expect(select).toBeVisible();
        const values = await select.locator('option').evaluateAll((options) => options.map((option) => option.value));
        expect(values.length).toBeGreaterThanOrEqual(1);
        expect(values).toContain(await select.inputValue());
    });

    test('loads services and moves to specialist step', async ({ page }) => {
        const serviceOptions = page.locator('#service_id option[value]:not([value=""])');
        if ((await serviceOptions.count()) === 0) {
            await expect(page.locator('#serviceCards')).toContainText(/No online-bookable services|لا توجد خدمات متاحة/);
            return;
        }
        await page.locator('#serviceCards .booking-choice').first().click();
        await expect(page.locator('[data-step="2"]')).toHaveClass(/active/);
        await expect(page.locator('#staffCards')).toBeVisible();
    });

    test('booking flow exposes real time slots when availability exists', async ({ page }) => {
        const serviceOptions = page.locator('#service_id option[value]:not([value=""])');
        if ((await serviceOptions.count()) === 0) test.skip(true, 'Tenant has no online-bookable services.');

        await page.locator('#serviceCards .booking-choice').first().click();
        await expect(page.locator('#staffCards .booking-choice').first()).toBeVisible({ timeout: 5000 });
        await page.locator('#staffCards .booking-choice').first().click();

        const dates = page.locator('#dateChoices .booking-date');
        await expect(dates.first()).toBeVisible();
        const slots = page.locator('#timeOptions .booking-slot');
        let found = false;
        for (let i = 0; i < Math.min(await dates.count(), 7); i += 1) {
            await dates.nth(i).click();
            try { await expect(slots.first()).toBeVisible({ timeout: 1800 }); found = true; break; } catch {}
        }
        test.skip(!found, 'No available slot in the displayed date window.');
        await slots.first().click();
        await page.locator('#name').fill('Browser Test Customer');
        await page.locator('#phone').fill('01000000000');
        await page.locator('#email').fill(`browser-test-${Date.now()}@example.test`);
        await expect(page.locator('#bookingStepDetails')).toHaveClass(/active/);
        await expect(page.locator('#submitBtn')).toBeEnabled();
    });

    test('deterministic booking UI reaches review with mocked booking data', async ({ page }) => {
        await installDeterministicBookingMocks(page);
        await page.reload({ waitUntil: 'networkidle' });
        await completeDeterministicBooking(page);
        await expect(page.locator('#bookingStepDetails')).toHaveClass(/active/);
        await expect(page.locator('#reviewService')).not.toHaveText('—');
        await expect(page.locator('#reviewDate')).not.toHaveText('—');
        await expect(page.locator('#reviewTime')).not.toHaveText('—');
        await expect(page.locator('#submitBtn')).toBeEnabled();
    });

    test('deterministic booking submit redirects to public queue reference', async ({ page }) => {
        await installDeterministicBookingMocks(page, { mockSubmit: true });
        await page.reload({ waitUntil: 'networkidle' });
        await completeDeterministicBooking(page);
        await expect(page.locator('#submitBtn')).toBeEnabled();
        await page.locator('#submitBtn').click();
        await page.waitForURL('**/queue/status?ref=VL-BROWSER01');
        await expect(page.locator('#lookup')).toHaveValue('VL-BROWSER01');
    });
});
