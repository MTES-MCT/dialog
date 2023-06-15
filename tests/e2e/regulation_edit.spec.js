// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Manage regulation location measures', async ({ page }) => {
    /**
     * Add first location and a maesure
     */

    await page.goto('/regulations/b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2');

    // Check regulation has no location yet
    const locations = page.getByRole('region', { name: 'Localisations' }).getByRole('list').first();
    expect(await locations.locator('> li').count()).toBe(0);

    // Fill mandatory location fields
    await page.locator('text=Voie ou ville').fill('Route du Grand Brossais, 44260 Savenay');

    // Fill empty measure
    const measureItem1 = page.getByTestId('measure-list').getByRole('listitem').nth(0);
    expect(await measureItem1.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 1');
    await measureItem1.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation à sens unique' });

    // Submit and check location was added with the given measure
    await page.getByRole('button', { name: 'Valider' }).click();
    const locationItem = locations.locator('> li').first();

    await locationItem.getByRole('heading', { level: 3, name: 'Route du Grand Brossais' }).waitFor();

    expect(locationItem).toContainText('Circulation à sens unique');

    /**
     * Add another measure to the now-existing location
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    const measureList = locationItem.getByRole('list', { name: 'Liste des restrictions' });
    await measureList.waitFor();
    expect(await measureList.locator('> li').count()).toBe(1);

    // Add a new measure
    await locationItem.getByRole('button', { name: 'Ajouter une restriction' }).click();
    const measureItem2 = measureList.locator('> li').nth(1);
    expect(await measureItem2.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 2');
    const restrictionType2 = measureItem2.getByRole('combobox', { name: 'Type de restriction' });
    expect(await restrictionType2.getAttribute('name')).toBe('location_form[measures][1][type]'); // Check field index.
    await restrictionType2.selectOption({ label: 'Circulation interdite' });

    // Add a period
    await measureItem2.getByRole('button', { name: 'Ajouter un créneau horaire' }).click();
    const periodItem = measureItem2.getByTestId('period-list').getByRole('listitem').nth(0);
    await periodItem.getByText('Lundi').click();
    await periodItem.getByText('Mardi').click();
    await periodItem.getByLabel("Heure de début").fill('08:00');
    await periodItem.getByLabel("Heure de fin").fill('08:30');

    // Submit and check new measure is shown
    await locationItem.getByRole('button', { name: 'Valider' }).click();

    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).toContainText('Circulation interdite du lundi au mardi de 08h00 à 08h30');

    /**
     * Change an existing measure
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();
    expect(await measureList.locator('> li').count()).toBe(2);

    // Change existing measure
    await measureItem2.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation alternée' });

    // Submit and check measure has been updated
    await locationItem.getByRole('button', { name: 'Valider' }).click();

    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).toContainText('Circulation alternée');

    /**
     * Delete a measure
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();

    // Delete 2nd measure
    await measureItem2.getByRole('button', { name: 'Supprimer' }).click();
    expect(await measureList.locator('> li').count()).toBe(1);

    // Add a new 2nd measure
    await locationItem.getByRole('button', { name: 'Ajouter une restriction' }).click();
    expect(await measureItem2.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 2');
    expect(await restrictionType2.getAttribute('name')).toBe('location_form[measures][2][type]'); // Check field index.
    await restrictionType2.selectOption({ label: 'Limitation de vitesse' });

    // Submit and check old 2nd measure has disappeared, and new 2nd measure has appeared.
    await locationItem.getByRole('button', { name: 'Valider' }).click();
    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).toContainText('Limitation de vitesse tous les jours');

    // Check new 2nd measure does not have any periods.
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();
    expect(await measureItem2.getByRole('group', { name: 'Créneaux horaires' }).locator('> li').count()).toBe(0);
});
