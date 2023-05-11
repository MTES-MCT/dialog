// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Display / hide other category field', async ({ page }) => {
    await page.goto('/regulations/add');

    const otherCategoryTextField = page.getByRole('textbox', { name: "Si autre, préciser la nature de l'arrêté" });
    await expect(otherCategoryTextField).not.toBeVisible();
    await page.getByRole('combobox', { name: 'Nature de l\'arrêté' }).selectOption({ label: 'Autre' });
    await expect(otherCategoryTextField).toBeVisible();
});
