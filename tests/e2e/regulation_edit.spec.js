// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Manage regulation location measures', async ({ page }) => {
    /**
     * Add first location and a maesure
     */

    await page.goto('/regulations/b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2');

    // Check regulation has no location yet
    const locations = page.getByRole('region', { name: 'Localisations' }).getByRole('list');
    expect(await locations.getByRole('listitem').count()).toBe(0);

    // Fill mandatory location fields
    await page.locator('text=Voie ou ville').fill('Route du Grand Brossais, 44260 Savenay');

    // Add a measure
    await page.getByRole('button', { name: 'Ajouter une restriction' }).click();
    const measureItem = page.getByRole('listitem').filter({ has: page.getByText('Restriction 1') });
    await measureItem.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation à sens unique' });

    // Submit and check location was added with the given measure
    await page.getByRole('button', { name: 'Valider' }).click();
    expect(await locations.getByRole('listitem').count()).toBe(1);
    const locationItem = locations.getByRole('listitem').first();
    await locationItem.getByRole('heading', { level: 3, name: 'Route du Grand Brossais' }).waitFor();
    expect(locationItem).toContainText('Circulation à sens unique');

    /**
     * Add another measure to the now-existing location
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    const measureList = locationItem.getByRole('list', { name: 'Liste des restrictions' });
    await measureList.waitFor();
    expect(await measureList.getByRole('listitem').count()).toBe(1);

    // Add a new measure
    await locationItem.getByRole('button', { name: 'Ajouter une restriction' }).click();
    const measureItem2 = locationItem.getByRole('listitem').filter({ has: page.getByText('Restriction 2') });
    await measureItem2.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation interdite' });

    // Submit and check new measure is shown
    await locationItem.getByRole('button', { name: 'Valider' }).click();
    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).toContainText('Circulation interdite');

    /**
     * Change an existing measure
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();
    expect(await measureList.getByRole('listitem').count()).toBe(2);

    // Change existing measure
    await measureItem2.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation alternée' });

    // Submit and check measure has been updated
    await locationItem.getByRole('button', { name: 'Valider' }).click();
    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).not.toContainText('Circulation interdite');
    await expect(locationItem).toContainText('Circulation alternée');
});
