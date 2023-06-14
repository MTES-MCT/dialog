// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test.describe('Regulation list', () => {
    test('Tabs', async ({ page }) => {
        await page.goto('/regulations');

        const temporaryTab = page.getByRole('tab', { name: 'Temporaires (3)' })
        await temporaryTab.waitFor();
        expect(temporaryTab).toHaveAttribute('aria-selected', 'true');
        const permanentTab = page.getByRole('tab', { name: 'Permanents (1)' });
        await permanentTab.waitFor();
        expect(permanentTab).toHaveAttribute('aria-selected', 'false');

        const temporaryTabPanel = page.getByRole('tabpanel', { name: 'Temporaires (3)' });
        const permanentTabPanel = page.getByRole('tabpanel', { name: 'Permanents (1)' });
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

        const deleteBtn = page.getByRole('button', { name: "Supprimer l'arrêté FO2/2023", exact: true });
        await deleteBtn.click();
        await expect(deleteModal).toBeVisible();

        // Don't delete
        await deleteModal.getByRole('button', { name: 'Ne pas supprimer', exact: true }).click();
        await expect(deleteModal).not.toBeVisible();
        await expect(page.getByText('FO2/2023', { exact: true })).toBeVisible();

        // Proceed to deletion
        await deleteBtn.click();
        await deleteModal.getByRole('button', { name: 'Supprimer', exact: true }).click();
        await expect(deleteModal).not.toBeVisible();
        await expect(page.getByText('FO2/2023', { exact: true })).not.toBeVisible();
    });
});
