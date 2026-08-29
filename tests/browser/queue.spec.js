import { test, expect } from '@playwright/test';

const queueUrl = '/queue/status';

test.describe('Velora public queue V3', () => {
    test('renders clean empty tracking surface', async ({ page }) => {
        await page.goto(queueUrl, { waitUntil: 'networkidle' });
        await expect(page.locator('.queue-header')).toBeVisible();
        await expect(page.locator('.queue-logo')).toBeVisible();
        await expect(page.locator('.queue-hero h1')).toHaveText(/Track|appointment|تابع|موعد/i);
        await expect(page.locator('#queueForm')).toBeVisible();
        await expect(page.locator('#lookup')).toBeVisible();
        await expect(page.locator('.queue-result')).toHaveCount(1);
        await expect(page.locator('.queue-result')).toHaveClass(/hidden/);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    });

    test('handles an unknown public reference without exposing internal data', async ({ page }) => {
        await page.goto(`${queueUrl}?ref=VL-NOT-FOUND`, { waitUntil: 'networkidle' });
        await expect(page.locator('#lookup')).toHaveValue('VL-NOT-FOUND');
        await expect(page.locator('#queueError')).toBeVisible({ timeout: 10000 });
        await expect(page.locator('#queueResult')).toHaveClass(/hidden/);
        await expect(page.locator('#queueResult')).not.toContainText('customer_id');
        await expect(page.locator('#queueResult')).not.toContainText('appointment_id');
    });
});
