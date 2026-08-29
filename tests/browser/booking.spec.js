import { test, expect } from '@playwright/test';

const bookingUrl = '/book';

test.describe('Velora public booking surface', () => {
    test('booking page renders tenant branding and has no horizontal overflow', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        await expect(page.locator('#bookingForm')).toBeVisible();
        await expect(page.locator('.vb2-brand')).toBeVisible();
        await expect(page.locator('.vb2-logo, .vb2-fallback-logo img').first()).toBeVisible();
        await expect(page.locator('.vb2-intro h1')).toBeVisible();
        await expect(page.locator('#service_id')).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
        expect(overflow).toBe(false);

        await page.screenshot({ path: 'artifacts/booking-desktop.png', fullPage: true });
    });

    test('mobile booking page stays within viewport and exposes touch-friendly controls', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        await expect(page.locator('#bookingForm')).toBeVisible();
        await expect(page.locator('.vb2-brand')).toBeVisible();
        await expect(page.locator('#service_id')).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
        expect(overflow).toBe(false);

        const controls = await page.locator('.vb2-icon-button, .vb2-language, #service_id').evaluateAll((items) =>
            items.map((item) => ({
                tag: item.tagName,
                height: item.getBoundingClientRect().height,
            })),
        );

        expect(controls.every((item) => item.height >= 40)).toBe(true);
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
        const servicesResponsePromise = page.waitForResponse((response) =>
            response.url().includes('/api/booking/services') && response.request().method() === 'GET',
        );

        await page.goto(bookingUrl, { waitUntil: 'domcontentloaded' });
        const servicesResponse = await servicesResponsePromise;
        const payload = await servicesResponse.json();

        const service = page.locator('#service_id');
        await expect(service).toBeVisible();

        if (!Array.isArray(payload.data) || payload.data.length === 0) {
            await expect(page.locator('#errorMessage')).toContainText('No online-bookable services are available right now.');
            return;
        }

        await expect(service.locator('option[value]:not([value=""])').first()).toBeAttached();
        await service.selectOption(await service.locator('option[value]:not([value=""])').first().getAttribute('value'));

        await expect(page.locator('#staffSection')).toBeVisible();
    });
});
