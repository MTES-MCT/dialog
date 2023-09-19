// @ts-check
const { test: base, expect } = require('@playwright/test');
const { RegulationOrderPage } = require('./pages/regulation_order.page');

const test = base.extend({
    regulationOrderPage: async ({ page }, use) => {
        const regulationOrderPage = new RegulationOrderPage(page);
        await use(regulationOrderPage);
        await regulationOrderPage.reset();
    }
});

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Begin then cancel a location', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    await page.beginNewLocation();
    await page.cancelLocation();
});

test('Add a minimal location', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const location = await page.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' });
    await expect(location).toContainText('Circulation interdite tous les jours');
});

test('Add a minimal measure to a location', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const location = await page.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' });
    await page.addMinimalMeasureToLocation(location, {
        expectedIndex: 1,
        expectedPosition: 2,
        restrictionType: 'Circulation à sens unique',
    });
    await expect(location).toContainText('Circulation interdite tous les jours');
    await expect(location).toContainText('Circulation à sens unique tous les jours');
});

test('Remove a measure and add another one to location', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const location = await page.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' });

    await page.addMinimalMeasureToLocation(location, {
        expectedIndex: 1,
        expectedPosition: 2,
        restrictionType: 'Circulation à sens unique',
    });
    await expect(location).toContainText('Circulation interdite tous les jours');
    await expect(location).toContainText('Circulation à sens unique tous les jours');

    await page.removeMeasureAndAddAnotherOne(location, {
        indexToRemove: 1,
        expectedIndex: 2,
        expectedPosition: 2,
        restrictionType: 'Limitation de vitesse',
        maxSpeed: '50',
    });
    await expect(location).toContainText('Circulation interdite tous les jours');
    await expect(location).not.toContainText('Circulation à sens unique tous les jours');
    await expect(location).toContainText('Vitesse limitée à 50 km/h tous les jours');

});

test('Set vehicles on a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const location = await page.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' });
    await page.setVehiclesOnMeasureAndAssertChangesWereSaved(location, {
        measureIndex: 0,
        restrictedVehicleTypes: ['Poids lourds'],
        otherRestrictedVehicleType: 'Matières dangereuses',
        exemptedVehicleTypes: ['Bus'],
        otherExemptedVehicleType: 'Déchets industriels',
    });
});

test('Add a period to a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const location = await page.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' });
    await expect(location).toContainText('Circulation interdite tous les jours');
    await page.addPeriodToMeasure(location, {
        measureIndex: 0,
        days: ['Lundi', 'Mardi'],
        startTime: '08:00',
        endTime: '08:30',
    });
    await expect(location).toContainText('Circulation interdite du lundi au mardi de 08h00 à 08h30');
});

test('Delete a period from a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const location = await page.addLocation({ address: 'Rue Monge, 21000 Dijon', restrictionType: 'Circulation interdite', expectedTitle: 'Rue Monge' });
    await page.addPeriodToMeasure(location, {
        measureIndex: 0,
        days: ['Lundi'],
        startTime: '08:00',
        endTime: '16:00',
    });
    await expect(location).toContainText('Circulation interdite le lundi de 08h00 à 16h00');
    await page.removePeriodFromMeasure(location, { measureIndex: 0, periodIndex: 0 });
    await expect(location).toContainText('Circulation interdite tous les jours');
});
