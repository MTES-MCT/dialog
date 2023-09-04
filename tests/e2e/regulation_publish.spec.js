// @ts-check
const { test, expect } = require('@playwright/test');
const { RegulationOrderPage } = require('./pages/regulation_order.page');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Create then publish a regulation order', async ({ page }) => {
    await page.goto('/regulations/add');

    // Create a regulation order
    await page.getByRole('textbox', { name: 'Identifiant' }).fill('F01/test');
    await page.getByRole('combobox', { name: 'Nature de l\'arrêté' }).selectOption('Travaux');
    await page.getByRole('textbox', { name: 'Description' }).fill('Example');
    await page.getByRole('button', { name: 'Continuer' }).click();

    // Inspect initial state
    await page.getByRole('heading', { level: 2, name: 'Arrêté permanent F01/test' }).waitFor();
    await expect(page.getByTestId('status-badge')).toHaveText('Brouillon');
    await expect(page.getByRole('link', { name: 'Télécharger le document' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Publier' })).toBeDisabled();

    // Add a location
    const regPage = new RegulationOrderPage(page);
    const location = await regPage.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' }, { doBegin: false });
    await expect(location).toContainText('Circulation interdite tous les jours');

    // Regulation order can now be downloaded and published
    await page.getByRole('link', { name: 'Télécharger le document' }).waitFor();
    await expect(page.getByRole('button', { name: 'Publier' })).toBeEnabled();

    // Publish for real
    await page.getByRole('button', { name: 'Publier' }).click();
    await page.getByRole('dialog', { name: 'Publier cet arrêté ?' }).getByRole('button', { name: 'Publier', exact: true }).click();
    await expect(page.getByTestId('status-badge')).toHaveText('Publié');
    await expect(page.getByRole('button', { name: 'Publier' })).not.toBeVisible();

    // Clean up
    await regPage.delete();
});
