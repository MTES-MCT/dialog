// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Regulation list mobile adjustments', async ({ page }) => {
    await page.goto('/regulations');

    const temporaryTabPanel = page.getByRole('tabpanel', { name: 'Temporaires (3)' });
    const table = temporaryTabPanel.getByRole('table', { name: 'Arrêtés de circulation' });

    // "Période" and "Statut" columns and corresponding row cells are hidden
    const header = table.getByRole('row').nth(0);
    await expect(header.getByRole('rowheader', { name: 'Période' })).not.toBeVisible();
    await expect(header.getByRole('columnheader', { name: 'Statut' })).not.toBeVisible();
    const regulationOrderRow = table.getByRole('row').nth(1);
    expect(await regulationOrderRow.getByRole('cell').count()).toBe(3);

    // Delete button is hidden
    await expect(regulationOrderRow.getByRole('button', { name: "Supprimer", exact: false })).not.toBeVisible();
});
