// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Manipulate location house number fields', async ({ page }) => {
    // Enter edit mode of a location
    await page.goto('/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const locations = page.getByRole('region', { name: 'Localisations' }).getByRole('list');
    const location = locations.getByRole('listitem').first();
    await location.getByRole('button', { name: 'Modifier' }).click();

    const fromHouseNumberField = location.locator('text=Numéro de début');
    await expect(fromHouseNumberField).not.toBeDisabled();
    const toHouseNumberField = location.locator('text=Numéro de fin');
    await expect(toHouseNumberField).not.toBeDisabled();

    // Search for a municipality
    const addressField = location.locator('text=Voie ou ville');
    await addressField.fill('Le Mesnil');
    // Check for address suggestions
    const optionList = location.getByRole('listbox', { name: 'Adresses suggérées' });
    await optionList.waitFor();
    expect(await optionList.getByRole('option').count()).toBe(4);
    // Click municipality option
    const firstOption = optionList.getByRole('option').first();
    await expect(firstOption).toHaveText('50580 Le Mesnil');
    await firstOption.click();
    // Check house number fields have been disabled
    await expect(fromHouseNumberField).toBeDisabled();
    await expect(toHouseNumberField).toBeDisabled();

    // House number fields are re-enabled if any change happens
    await addressField.dispatchEvent('change');
    await expect(fromHouseNumberField).not.toBeDisabled();
    await expect(toHouseNumberField).not.toBeDisabled();

    // Select a street option
    await addressField.fill('Rue Eugène Berthoud');
    await expect(firstOption).toHaveText('Rue Eugène Berthoud, 93400 Saint-Ouen-sur-Seine');
    await firstOption.click();
    // Check fields were not disabled
    await expect(fromHouseNumberField).not.toBeDisabled();
    await expect(toHouseNumberField).not.toBeDisabled();
});

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
    const measureItem1 = page.getByTestId('measure-list').getByRole('listitem').nth(0);
    expect(await measureItem1.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 1');
    await measureItem1.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation à sens unique' });

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
    const measureItem2 = locationItem.getByRole('listitem').nth(1);
    expect(await measureItem2.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 2');
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

    /**
     * Delete a measure
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();

    // Delete measure
    await measureItem2.getByRole('button', { name: 'Supprimer' }).click();
    expect(await measureList.getByRole('listitem').count()).toBe(1);

    // Check that indices in measure item titles have been synced...
    // * Measure previously shown as "Restriction 2" has become "Restriction 1"
    expect(await measureItem1.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 1');
    await expect(measureItem1.getByRole('combobox', { name: 'Type de restriction' })).toHaveValue('oneWayTraffic');
    // * A new measure would have index 2 (instead of index 3 if indices hadn't been synced)
    await locationItem.getByRole('button', { name: 'Ajouter une restriction' }).click();
    const tempMeasureItem = measureList.getByRole('listitem').nth(1);
    expect(await tempMeasureItem.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 2');
    await tempMeasureItem.getByRole('button', { name: 'Supprimer' }).click();

    // Submit and check measure has been deleted
    await locationItem.getByRole('button', { name: 'Valider' }).click();
    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).not.toContainText('Circulation alternée');
});
