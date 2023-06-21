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
    await measureItem1.getByRole('radiogroup', { name: 'Véhicules concernés' }).getByText('Tous les véhicules', { exact: true }).click();
    // Restricted vehicles options are not shown
    await expect(measureItem1.getByLabel('Poids lourds', { exact: true })).not.toBeVisible();
    // Exempted vehicles options are not shown
    await expect(measureItem1.getByLabel('Bus', { exact: true })).not.toBeVisible();

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

    // Define restricted vehicles
    const restrictedVehicles2 = measureItem2.getByRole('radiogroup', { name: 'Véhicules concernés' });
    await restrictedVehicles2.getByText('Certains véhicules', { exact: true }).click();
    await restrictedVehicles2.getByText('Poids lourds', { exact: true }).click();
    const otherRestrictedVehicleTypeText2 = restrictedVehicles2.getByRole('textbox', { name: 'Autres' });
    await expect(otherRestrictedVehicleTypeText2).not.toBeVisible();
    await restrictedVehicles2.getByLabel('Autres', { exact: true }).click();
    await otherRestrictedVehicleTypeText2.fill('Matières dangereuses');
    // Define exempted vehicles
    const exemptedVehicles2 = measureItem2.getByRole('group', { name: 'Sauf...'});
    await exemptedVehicles2.getByRole('button', { name: 'Définir une exception' }).click();
    await exemptedVehicles2.getByText('Bus', { exact: true }).click();
    const otherExemptedVehicleTypeText2 = exemptedVehicles2.getByRole('textbox', { name: 'Autres' });
    await expect(otherExemptedVehicleTypeText2).not.toBeVisible();
    await exemptedVehicles2.getByLabel('Autres', { exact: true }).click();
    await otherExemptedVehicleTypeText2.fill('Déchets industriels');

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

    // Check restricted vehicles and exempted vehicles were saved
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    expect(await restrictedVehicles2.getByText('Poids lourds', { exact: true }).getAttribute('aria-pressed')).toBe('true');
    expect(await restrictedVehicles2.getByLabel('Autres', { exact: true }).getAttribute('aria-pressed')).toBe('true');
    await expect(restrictedVehicles2.getByRole('textbox', { name: 'Autres' })).toHaveValue('Matières dangereuses');
    expect(await exemptedVehicles2.getByText('Bus', { exact: true }).getAttribute('aria-pressed')).toBe('true');
    expect(await exemptedVehicles2.getByLabel('Autres', { exact: true }).getAttribute('aria-pressed')).toBe('true');
    await expect(exemptedVehicles2.getByRole('textbox', { name: 'Autres' })).toHaveValue('Déchets industriels');
    await locationItem.getByRole('button', { name: 'Annuler' }).click();

    /**
     * Change an existing measure
     */

    // Enter location edit mode
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();
    expect(await measureList.locator('> li').count()).toBe(2);

    // Change existing measure
    await measureItem2.getByRole('combobox', { name: 'Type de restriction' }).selectOption({ label: 'Circulation alternée' });
    await measureItem2.getByRole('radiogroup', { name: 'Véhicules concernés' }).getByText('Tous les véhicules', { exact: true }).click();
    await expect(restrictedVehicles2.getByText('Poids lourds', { exact: true })).not.toBeVisible();
    await expect(exemptedVehicles2.getByText('Bus', { exact: true })).toBeVisible();

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
    await measureItem2.getByRole('button', { name: 'Supprimer', exact: true }).nth(0).click();
    expect(await measureList.locator('> li').count()).toBe(1);

    // Add a new 2nd measure
    await locationItem.getByRole('button', { name: 'Ajouter une restriction' }).click();
    expect(await measureItem2.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 2');
    expect(await restrictionType2.getAttribute('name')).toBe('location_form[measures][2][type]'); // Check field index.
    await restrictionType2.selectOption({ label: 'Limitation de vitesse' });
    await measureItem2.getByRole('radiogroup', { name: 'Véhicules concernés' }).getByText('Tous les véhicules', { exact: true }).click();

    // Submit and check old 2nd measure has disappeared, and new 2nd measure has appeared.
    await locationItem.getByRole('button', { name: 'Valider' }).click();
    await expect(locationItem).toContainText('Circulation à sens unique');
    await expect(locationItem).toContainText('Limitation de vitesse tous les jours');

    // Check new 2nd measure does not have any periods.
    await locationItem.getByRole('button', { name: 'Modifier' }).click();
    await measureList.waitFor();
    expect(await measureItem2.getByRole('group', { name: 'Créneaux horaires' }).locator('> li').count()).toBe(0);
});

test('Delete a period', async ({ page }) => {
    await page.goto('/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

    // Open first location
    const locations = page.getByRole('region', { name: 'Localisations' }).getByRole('list').first();
    const locationItem = locations.locator('> li').first();

    await expect(locationItem).toContainText('Circulation interdite du lundi au mardi de 08h00 à 22h00');

    await locationItem.getByRole('button', { name: 'Modifier' }).click();

    let measureList = locationItem.getByRole('list', { name: 'Liste des restrictions' });
    await measureList.waitFor();

    const measureItem = measureList.locator('> li').nth(0);
    expect(await measureItem.getByRole('heading', { level: 4 }).innerText()).toBe('Restriction 1');

    await measureItem.getByTestId('delete-period-1').click();

    await page.getByRole('button', { name: 'Valider' }).click();
    await expect(locationItem).toContainText('Circulation interdite tous les jours');
});
