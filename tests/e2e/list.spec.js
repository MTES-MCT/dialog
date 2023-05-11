// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();

    // Create a regulation to delete
    await page.goto('/regulations/add');
    await page.getByRole('textbox', { name: 'Identifiant' }).fill('F-test/list');
    await page.getByRole('textbox', { name: 'Description' }).fill('Description');
    await page.getByRole('button', { name: 'Continuer' }).click();
});

test.describe('Regulation list', () => {
    test('Tabs', async ({ page }) => {
        await page.goto('/regulations');

        const temporaryTab = page.getByRole('tab', { name: 'Temporaires (3)' })
        await temporaryTab.waitFor();
        expect(temporaryTab).toHaveAttribute('aria-selected', 'true');
        const permanentTab = page.getByRole('tab', { name: 'Permanents (2)' });
        await permanentTab.waitFor();
        expect(permanentTab).toHaveAttribute('aria-selected', 'false');

        const temporaryTabPanel = page.getByRole('tabpanel', { name: 'Temporaires (3)' });
        const permanentTabPanel = page.getByRole('tabpanel', { name: 'Permanents (2)' });
        await expect(temporaryTabPanel).toBeVisible();
        await expect(permanentTabPanel).not.toBeVisible();

        await permanentTab.click();
        expect(temporaryTab).toHaveAttribute('aria-selected', 'false');
        await expect(temporaryTabPanel).not.toBeVisible();
        expect(permanentTab).toHaveAttribute('aria-selected', 'true');
        await expect(permanentTabPanel).toBeVisible();
    });

    test('Delete modal', async ({ page }) => {
        await page.goto('/regulations');

        const deleteModal = page.locator('[id="regulation-delete-modal"]');
        await expect(deleteModal).not.toBeVisible();

        await page.getByRole('tab', { name: 'Permanents (2)' }).click();
        const deleteBtn = page.getByRole('button', { name: "Supprimer l'arrêté F-test/list", exact: true });
        await deleteBtn.click();
        await expect(deleteModal).toBeVisible();

        // Don't delete
        await deleteModal.getByRole('button', { name: 'Ne pas supprimer', exact: true }).click();
        await expect(deleteModal).not.toBeVisible();
        await expect(page.getByText('F-test/list', { exact: true })).toBeVisible();

        // Proceed to deletion
        await deleteBtn.click();
        await deleteModal.getByRole('button', { name: 'Supprimer', exact: true }).click();
        await expect(deleteModal).not.toBeVisible();
        await expect(page.getByText('F-test/list', { exact: true })).not.toBeVisible();
    });
});
