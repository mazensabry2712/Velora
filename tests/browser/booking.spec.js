import { test, expect } from '@playwright/test';

const bookingUrl = '/book';

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
        const serviceSelect = page.locator('#service_id');
        const serviceOptions = serviceSelect.locator('option[value]:not([value=""])');

        await page.locator('#serviceCards .booking-empty').waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});
        const serviceCount = await serviceOptions.count();
        if (serviceCount === 0) {
            await expect(page.locator('#serviceCards')).toContainText(/No online-bookable services|لا توجد خدمات متاحة/);
            return;
        }

        await page.locator('#serviceCards .booking-choice').first().click();
        await expect(page.locator('[data-step="2"]')).toBeVisible();
        await expect(page.locator('#staffCards')).toBeVisible();
    });

    test('booking flow exposes real time slots when availability exists', async ({ page }) => {
        const service = page.locator('#service_id');
        const serviceOptions = service.locator('option[value]:not([value=""])');
        const serviceCount = await serviceOptions.count();

        if (serviceCount === 0) {
            test.skip(true, 'Tenant has no online-bookable services.');
        }

        await page.locator('#serviceCards .booking-choice').first().click();
        await expect(page.locator('#staffCards .booking-choice').first()).toBeVisible({ timeout: 5000 });
        await page.locator('#staffCards .booking-choice').first().click();

        const dates = page.locator('#dateChoices .booking-date');
        await expect(dates.first()).toBeVisible();
        const slots = page.locator('#timeOptions .booking-slot');
        let found = false;

        for (let i = 0; i < Math.min(await dates.count(), 7); i += 1) {
            await dates.nth(i).click();
            try {
                await expect(slots.first()).toBeVisible({ timeout: 1800 });
                found = true;
                break;
            } catch {}
        }

        test.skip(!found, 'No available slot in the displayed date window.');
        await slots.first().click();
        await page.locator('#name').fill('Browser Test Customer');
        await page.locator('#phone').fill('01000000000');
        await page.locator('#email').fill(`browser-test-${Date.now()}@example.test`);
        await expect(page.locator('#bookingStepDetails')).toHaveClass(/active/);
        await expect(page.locator('#submitBtn')).toBeEnabled();
    });
});
