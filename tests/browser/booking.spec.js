import { test, expect } from '@playwright/test';

const bookingUrl = '/book';

test.describe('Velora public booking surface', () => {
    test('desktop booking page renders tenant branding and has no horizontal overflow', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        await expect(page.locator('#bookingForm')).toBeVisible();
        await expect(page.locator('.vb2-brand')).toBeVisible();
        await expect(page.locator('.vb2-logo')).toBeVisible();
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

        const controls = await page.locator('.vb2-icon-button, .vb2-language select, #service_id').evaluateAll((items) =>
            items.map((item) => ({
                tag: item.tagName,
                height: item.getBoundingClientRect().height,
            })),
        );

        expect(controls.every((item) => item.height >= 40)).toBe(true);
        await page.screenshot({ path: 'artifacts/booking-mobile.png', fullPage: true });
    });

    test('tenant language control only exposes configured languages', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        const languageValues = await page.locator('.vb2-language select option').evaluateAll((options) => options.map((option) => option.value));
        expect(languageValues).toEqual(expect.arrayContaining(['ar', 'en']));
        expect(languageValues).not.toContain('fr');
    });

    test('service selection reveals the next booking step in the browser', async ({ page }) => {
        await page.goto(bookingUrl, { waitUntil: 'networkidle' });

        const service = page.locator('#service_id');
        await expect(service.locator('option')).not.toHaveCount(1);

        const firstBookableOption = service.locator('option[value]:not([value=""])').first();
        await expect(firstBookableOption).toBeAttached();
        await service.selectOption(await firstBookableOption.getAttribute('value'));

        await expect(page.locator('#staffSection')).toBeVisible();
    });
});
