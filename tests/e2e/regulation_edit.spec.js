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

test('Begin then cancel a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    await page.beginNewMeasure();
    await page.cancelMeasure();
});

test('Add a minimal measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const measure = await page.addMeasureWithLocation({
        cityLabel: 'Dijon',
        roadName: 'Rue Monge',
        expectedIndex: 2,
        restrictionType: 'Circulation interdite',
    });

    await expect(measure).toContainText('Dijon (21000)');
    await expect(measure).toContainText('tous les jours');
    await expect(measure).toContainText('pour tous les véhicules');
    await expect(measure).toContainText('Circulation interdite');
});

test('Set vehicles on a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const measure = await page.addMeasureWithLocation({
        cityLabel: 'Dijon',
        roadName: 'Rue Monge',
        expectedIndex: 2,
        restrictionType: 'Circulation interdite',
    });

    await page.setVehiclesOnMeasureAndAssertChangesWereSaved(measure, {
        restrictedVehicleTypes: ['Poids lourds', 'Gabarit'],
        otherRestrictedVehicleType: 'Matières dangereuses',
        exemptedVehicleTypes: ['Transports en commun'],
        otherExemptedVehicleType: 'Déchets industriels',
    });
});

test('Add a period to a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const measure = await page.addMeasureWithLocation({
        cityLabel: 'Dijon',
        roadName: 'Rue Monge',
        expectedIndex: 2,
        restrictionType: 'Circulation interdite',
    });

    await page.addPeriodToMeasure(measure, {
        days: ['Lundi', 'Mardi'],
        dayOption: 'Certains jours',
        startDate: '2023-10-10',
        startTime: ['08', '00'],
        endDate: '2023-10-10',
        endTime: ['08', '30'],
    });
    await expect(measure).toContainText('du 09/06/2023 à 10h00 au 09/06/2023 à 10h00, du lundi au mardi');
});

test('Delete a period from a measure', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const measure = await page.addMeasureWithLocation({
        cityLabel: 'Dijon',
        roadName: 'Rue Monge',
        expectedIndex: 2,
        restrictionType: 'Circulation interdite',
    });
    await page.addPeriodToMeasure(measure, {
        days: ['Lundi'],
        dayOption: 'Certains jours',
        startDate: '2023-10-10',
        startTime: ['08', '00'],
        endDate: '2023-10-10',
        endTime: ['16', '00'],
    });
    await expect(measure).toContainText('du 09/06/2023 à 10h00 au 09/06/2023 à 10h00, le lundi');
    await page.removePeriodFromMeasure(measure, { periodIndex: 0 });
    await expect(measure).toContainText('tous les jours');
});

test('Manipulate time slots', async ({ regulationOrderPage }) => {
    /** @type {RegulationOrderPage} */
    let page = regulationOrderPage;

    await page.goToRegulation('e413a47e-5928-4353-a8b2-8b7dda27f9a5');
    const measure = await page.addMeasureWithLocation({
        cityLabel: 'Dijon',
        roadName: 'Rue Monge',
        expectedIndex: 2,
        restrictionType: 'Circulation interdite',
    });
    await page.manipulateTimeSlots(measure);
});
