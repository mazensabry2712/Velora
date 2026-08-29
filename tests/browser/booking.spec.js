import { test, expect } from '@playwright/test';

const bookingUrl = '/book';

test.describe('Velora public booking surface', () => {
    async function loadServices(page) {
        const servicesResponsePromise = page.waitForResponse((response) =>
            response.url().includes('/api/booking/services') && response.request().method() === 'GET',
        );

        await page.goto(bookingUrl, { waitUntil: 'domcontentloaded' });
        const servicesResponse = await servicesResponsePromise;
        return servicesResponse.json();
    }

    test('booking page renders tenant branding and has no horizontal overflow', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        await expect(page.locator('#bookingForm')).toBeVisible();
        await expect(page.locator('.vb2-brand')).toBeVisible();
        await expect(page.locator('.vb2-logo, .vb2-fallback-logo img').first()).toBeVisible();
        await expect(page.locator('.vb2-intro h1')).toBeVisible();
        await expect(page.locator('#service_id')).toBeAttached();
        await expect(page.locator('#successMessage')).toHaveCount(0);
        await expect(page.locator('.vb-final-v2-summary')).toBeVisible();
        await expect(page.locator('.vb-final-v2-progress')).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
        expect(overflow).toBe(false);

        await page.screenshot({ path: 'artifacts/booking-desktop.png', fullPage: true });
    });

    test('mobile booking page stays within viewport and exposes touch-friendly controls', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        await expect(page.locator('#bookingForm')).toBeVisible();
        await expect(page.locator('.vb2-brand')).toBeVisible();
        await expect(page.locator('#service_id')).toBeAttached();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
        expect(overflow).toBe(false);

        const controls = await page.locator('.vb2-icon-button, .vb2-language, .vb-final-v2-choice, .vb-final-v2-btn').evaluateAll((items) =>
            items.map((item) => item.getBoundingClientRect().height),
        );

        expect(controls.length).toBeGreaterThan(0);
        expect(controls.every((height) => height >= 40)).toBe(true);
        await page.screenshot({ path: 'artifacts/booking-mobile.png', fullPage: true });
    });

    test('tenant language control renders configured language choices', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        const languageSelect = page.locator('.vb2-language select');
        await expect(languageSelect).toBeVisible();

        const languageValues = await languageSelect.locator('option').evaluateAll((options) => options.map((option) => option.value));
        expect(languageValues.length).toBeGreaterThanOrEqual(1);
        expect(languageValues).toContain(await languageSelect.inputValue());
    });

    test('service selection reveals the next booking step when services are available', async ({ page }) => {
        const payload = await loadServices(page);
        const service = page.locator('#service_id');
        await expect(service).toBeAttached();

        if (!Array.isArray(payload.data) || payload.data.length === 0) {
            await expect(page.locator('#errorMessage')).toContainText('No online-bookable services are available right now.');
            return;
        }

        const first = service.locator('option[value]:not([value=""])').first();
        await expect(first).toBeAttached();
        await service.selectOption(await first.getAttribute('value'));

        await expect(page.locator('#staffSection')).toHaveClass(/vb-final-v2-active/);
        await expect(page.locator('#staffCards')).toBeVisible();
    });

    test('booking flow reaches ready-to-submit state with a real available slot', async ({ page }) => {
        const payload = await loadServices(page);
        if (!Array.isArray(payload.data) || payload.data.length === 0) {
            test.skip(true, 'Tenant has no online-bookable services.');
        }

        const service = page.locator('#service_id');
        await service.selectOption(await service.locator('option[value]:not([value=""])').first().getAttribute('value'));
        await expect(page.locator('#staffCards .vb-final-v2-choice').first()).toBeVisible();
        await page.locator('#staffCards .vb-final-v2-choice').nth(1).click().catch(async () => {
            await page.locator('#staffCards .vb-final-v2-choice').first().click();
        });

        const date = page.locator('#appointment_date');
        const time = page.locator('#appointment_time');
        let foundSlot = false;

        for (let offset = 0; offset < 14 && !foundSlot; offset += 1) {
            const candidate = await page.evaluate((days) => {
                const date = new Date();
                date.setDate(date.getDate() + days);
                return date.toISOString().slice(0, 10);
            }, offset);

            await date.fill(candidate);
            await date.dispatchEvent('change');

            try {
                await expect(time.locator('option[value]:not([value=""])').first()).toBeAttached({ timeout: 1500 });
                foundSlot = true;
            } catch {
                // Try the next date.
            }
        }

        test.skip(!foundSlot, 'No available slot found in the next 14 days.');

        const slot = page.locator('#vbFinalSlots .vb-final-v2-slot').first();
        await expect(slot).toBeVisible();
        await slot.click();

        await page.locator('#name').fill('Browser Test Customer');
        await page.locator('#email').fill(`browser-test-${Date.now()}@example.test`);
        await page.locator('#phone').fill('01000000000');

        await expect(page.locator('#detailsSection, [data-step-panel="4"]')).toHaveClass(/vb-final-v2-active/);
        await expect(page.locator('#notes')).toBeAttached();
        await expect(page.locator('#submitBtn')).toBeVisible();
        await expect(page.locator('#submitBtn')).toBeEnabled();

        await page.screenshot({ path: 'artifacts/booking-ready-to-submit.png', fullPage: true });
    });
});
