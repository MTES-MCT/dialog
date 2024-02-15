// @ts-check
const { expect } = require('@playwright/test');

/** @typedef {import('@playwright/test').Page} Page */
/** @typedef {import('@playwright/test').Locator} Locator */

export class RegulationOrderPage {
    /**
     * @param {Page} page
     */
    constructor(page) {
        this.page = page;
        this._measures = page.getByRole('region', { name: 'Dispositif' }).getByRole('list').first();
        /** @type {string[]} */
        this.addedMeasuresTitles = [];
        this.addBtn = page.getByRole('button', { name: 'Ajouter une mesure' });
        this.saveBtn = page.getByRole('button', { name: 'Valider' });
    }

    /**
     * @param {string} uuid
     */
    async goToRegulation(uuid) {
        await this.page.goto(`/regulations/${uuid}`);
    }

    async beginNewMeasure() {
        await this.addBtn.click();
        await expect(this.addBtn).not.toBeVisible();
    }

    /**
     * @param {Locator|undefined} measure
     */
    async cancelMeasure(measure = undefined) {
        const cancelBtn = (measure || this.page).getByRole('button', { name: 'Annuler' });
        await cancelBtn.click();
        await expect(cancelBtn).not.toBeVisible();
        await this.addBtn.waitFor();
    }

    /**
     * @param {string} title
     * @returns Locator
     */
    getMeasureByTitle(title) {
        return this._measures.locator('> li').filter({
            has: this.page.getByRole('heading', { level: 3, name: title }),
        });
    }

    /**
     * @param {{maxSpeed?: string, cityLabel: string, roadName: string, expectedIndex: number, restrictionType: string}} args
     * @param {{doBegin: boolean}} options
     *
     * @returns Locator
     */
    async addMeasureWithLocation({ cityLabel, roadName, maxSpeed, restrictionType, expectedIndex }, { doBegin } = { doBegin: true }) {
        if (doBegin) {
            await this.beginNewMeasure();
        }

        const restrictionTypeField = this.page.getByRole('combobox', { name: 'Type de restriction' });
        expect(await restrictionTypeField.getAttribute('name')).toBe(`measure_form[type]`);
        await restrictionTypeField.selectOption({ label: restrictionType });

        if (maxSpeed) {
            await this.page.getByLabel('Vitesse maximale autorisée').fill(maxSpeed);
        }

        await this.page.getByTestId(`allVehicles-${expectedIndex}-yes`).click();

        // Advanced vehicles options are not shown
        await expect(this.page.getByLabel('Types de véhicules concernés', { exact: true })).not.toBeVisible(); // Restricted
        await expect(this.page.getByLabel('Indiquez les exceptions à la restriction', { exact: true })).not.toBeVisible(); // Exempted

        await this.page.getByRole('button', { name: 'Ajouter une localisation' }).click();

        await this.page.getByLabel('Ville ou commune').fill(cityLabel);
        await this.page.getByRole('listbox', {name: 'Noms de communes suggérés'}).getByRole('option').first().click();
        await this.page.getByRole('textbox', {name: 'Voie'}).fill(roadName);

        await this.saveBtn.click();

        this.addedMeasuresTitles.push(restrictionType);
        return this.getMeasureByTitle(restrictionType);
    }

    /**
     * @param {{cityLabel: string, roadName: string, restrictionType: string, expectedTitle: string}} args
     * @param {{doBegin: boolean}} options
     *
     * @returns Locator
     */
    async addLocation({ cityLabel, roadName, restrictionType, expectedTitle }, { doBegin } = { doBegin: true }) {
        if (doBegin) {
            await this.beginNewLocation();
        }

        await this.page.getByLabel('Ville ou commune').fill(cityLabel);
        await this.page.getByRole('listbox', {name: 'Noms de communes suggérés'}).getByRole('option').first().click();
        await this.page.getByRole('textbox', {name: 'Voie'}).fill(roadName);
        await this.page.getByLabel('Type de restriction').selectOption({ label: restrictionType });
        await this.page.getByTestId('allVehicles-0-yes').click();
        await this.saveBtn.click();
        this.addedLocationTitles.push(expectedTitle);
        return this.getLocationByTitle(expectedTitle);
    }

    /**
     * @param {Locator} location
     */
    async _beginEditLocation(location) {
        await location.getByRole('button', { name: 'Modifier' }).click();
    }

    /**
     * @param {Locator} location
     */
    async _endEditLocation(location) {
        await this.saveBtn.click();
        await this._waitForReadMode(location);
    }

    /**
     * @param {Locator} location
     * @param {{expectedIndex: number, expectedPosition: number, restrictionType: string, isAlreadyEditing?: boolean, maxSpeed?: string}} options
     */
    async _doAddMinimalMeasureToLocation(location, { expectedIndex, expectedPosition, restrictionType, maxSpeed, isAlreadyEditing = false }) {
        await location.getByRole('button', { name: 'Ajouter une restriction' }).click();

        const measure = location.getByTestId('measure-list').getByRole('listitem').last();
        expect(await measure.getByRole('heading', { level: 4 }).innerText()).toBe(`Restriction ${expectedPosition}`);

        const restrictionTypeField = measure.getByRole('combobox', { name: 'Type de restriction' });
        expect(await restrictionTypeField.getAttribute('name')).toBe(`location_form[measures][${expectedIndex}][type]`);
        await restrictionTypeField.selectOption({ label: restrictionType });

        if (maxSpeed) {
            await measure.getByLabel('Vitesse maximale autorisée').fill(maxSpeed);
        }

        await this.page.getByTestId(`allVehicles-${expectedIndex}-yes`).click();

        // Advanced vehicles options are not shown
        await expect(measure.getByLabel('Types de véhicules concernés', { exact: true })).not.toBeVisible(); // Restricted
        await expect(measure.getByLabel('Indiquez les exceptions à la restriction', { exact: true })).not.toBeVisible(); // Exempted
    }

    /**
     * @param {Locator} location
     * @param {{ expectedIndex: number, expectedPosition: number, restrictionType: string, maxSpeed?: string }} options
     */
    async addMinimalMeasureToLocation(location, { expectedIndex, expectedPosition, restrictionType, maxSpeed }) {
        await this._beginEditLocation(location);
        await this._doAddMinimalMeasureToLocation(location, { expectedIndex, expectedPosition, restrictionType, maxSpeed });
        await this.saveBtn.click();
        await this._waitForReadMode(location);
    }

    /**
     * @param {Locator} location
    * @param {{ indexToRemove: number, expectedIndex: number, expectedPosition: number, restrictionType: string, maxSpeed?: string }} options
     */
    async removeMeasureAndAddAnotherOne(location, { indexToRemove, expectedIndex, expectedPosition, restrictionType, maxSpeed }) {
        await this._beginEditLocation(location);
        const measure = location.getByTestId('measure-list').getByRole('listitem').nth(indexToRemove);
        await measure.getByRole('button', { name: 'Supprimer' }).click();
        await this._doAddMinimalMeasureToLocation(location, { expectedIndex, expectedPosition, restrictionType, maxSpeed });
        await this._endEditLocation(location);
    }

    /**
     * @param {Locator} location
     * @param {{
     *   measureIndex: number,
     *   restrictedVehicleTypes: string[],
     *   otherRestrictedVehicleType: string,
     *   exemptedVehicleTypes: string[],
     *   otherExemptedVehicleType: string,
     * }} options
     */
    async setVehiclesOnMeasureAndAssertChangesWereSaved(location, {
        measureIndex,
        restrictedVehicleTypes,
        otherRestrictedVehicleType,
        exemptedVehicleTypes,
        otherExemptedVehicleType,
    }) {
        await this._beginEditLocation(location);

        const measure = location.getByTestId('measure-list').getByRole('listitem').nth(measureIndex);

        // Define restricted vehicles
        const restrictedVehiclesFieldset = measure.getByRole('radiogroup', { name: 'Véhicules concernés' });
        await restrictedVehiclesFieldset.getByTestId(`allVehicles-${measureIndex}-no`).click();
        for (const vehicleType of restrictedVehicleTypes) {
            await restrictedVehiclesFieldset.getByLabel('Types de véhicules concernés').getByLabel(vehicleType, { exact: true }).click();
        }
        const otherRestrictedVehicleTypeField = restrictedVehiclesFieldset.getByRole('textbox', { name: 'Autres' });
        await expect(otherRestrictedVehicleTypeField).not.toBeVisible();
        await restrictedVehiclesFieldset.getByLabel('Autres', { exact: true }).click();
        await otherRestrictedVehicleTypeField.fill(otherRestrictedVehicleType);

        if (restrictedVehicleTypes.includes('Poids lourds')) {
            await expect(restrictedVehiclesFieldset.getByRole('textbox', { name: 'Poids maximum' })).toHaveValue('3,5');
        }

        if (restrictedVehicleTypes.includes('Gabarit')) {
            await restrictedVehiclesFieldset.getByRole('textbox', { name: 'Hauteur maximum' }).fill('2,4');
        }

        // Define exempted vehicles
        const exemptedVehiclesFieldset = measure.getByRole('group', { name: 'Sauf...' });
        const defineExceptionBtn = exemptedVehiclesFieldset.getByRole('button', { name: 'Définir une exception' })
        await defineExceptionBtn.click();
        await expect(defineExceptionBtn).not.toBeVisible();
        for (const vehicleType of exemptedVehicleTypes) {
            await exemptedVehiclesFieldset.getByText(vehicleType, { exact: true }).click();
        }
        const otherExemptedVehicleTypeField = exemptedVehiclesFieldset.getByRole('textbox', { name: 'Autres' });
        await expect(otherExemptedVehicleTypeField).not.toBeVisible();
        await exemptedVehiclesFieldset.getByLabel('Autres', { exact: true }).click();
        await otherExemptedVehicleTypeField.fill(otherExemptedVehicleType);

        await this._endEditLocation(location);

        // Check changes were saved by inspecting the edit view
        await this._beginEditLocation(location);
        for (const vehicleType of restrictedVehicleTypes) {
            expect(await restrictedVehiclesFieldset.getByLabel('Types de véhicules concernés').getByText(vehicleType, { exact: true }).getAttribute('aria-pressed')).toBe('true');
        }
        expect(await restrictedVehiclesFieldset.getByLabel('Autres', { exact: true }).getAttribute('aria-pressed')).toBe('true');
        await expect(restrictedVehiclesFieldset.getByRole('textbox', { name: 'Autres' })).toHaveValue(otherRestrictedVehicleType);

        if (restrictedVehicleTypes.includes('Poids lourds')) {
            await expect(restrictedVehiclesFieldset.getByRole('textbox', { name: 'Poids maximum' })).toHaveValue('3,5');
        }

        if (restrictedVehicleTypes.includes('Gabarit')) {
            await expect(restrictedVehiclesFieldset.getByRole('textbox', { name: 'Hauteur maximum' })).toHaveValue('2,4');
        }

        for (const vehicleType of exemptedVehicleTypes) {
            expect(await exemptedVehiclesFieldset.getByText(vehicleType, { exact: true }).getAttribute('aria-pressed')).toBe('true');
        }
        expect(await exemptedVehiclesFieldset.getByLabel('Autres', { exact: true }).getAttribute('aria-pressed')).toBe('true');
        await expect(exemptedVehiclesFieldset.getByRole('textbox', { name: 'Autres' })).toHaveValue(otherExemptedVehicleType);
        await this.cancelLocation(location);
    }

    /**
     * @param {Locator} location
     * @param {{ measureIndex: number, days: string[], startDate: string, startTime: string[], endDate: string, endTime: string[], dayOption: string }} options
     */
    async addPeriodToMeasure(location, { measureIndex, days, startDate, startTime, endDate, endTime, dayOption }) {
        await this._beginEditLocation(location);

        const measure = location.getByTestId('measure-list').getByRole('listitem').nth(measureIndex);
        await measure.getByRole('button', { name: 'Ajouter une plage' }).click();
        const period = measure.getByTestId('period-list').getByRole('listitem').nth(0);
        await this.page.getByLabel('Quels jours sont concernés ?').selectOption({ label: dayOption });

        await period.getByTestId('start').nth(0).selectOption({ label: startTime[0]});
        await period.getByTestId('start').nth(1).selectOption({ label: startTime[1]});

        await period.getByTestId('end').nth(0).selectOption({ label: endTime[0]});
        await period.getByTestId('end').nth(1).selectOption({ label: endTime[1]});

        await period.getByLabel('Date de début').fill(startDate);
        await period.getByLabel('Date de fin').fill(endDate);


        if (dayOption == 'Certains jours') {
            for (const day of days) {
                await period.getByText(day).click();
            }
        }

        await this._endEditLocation(location);
    }

    /**
     * @param {Locator} location
     * @param {{ measureIndex: number, periodIndex: number}} options
     */
    async removePeriodFromMeasure(location, { measureIndex, periodIndex }) {
        await this._beginEditLocation(location);

        const measure = location.getByTestId('measure-list').getByRole('listitem').nth(measureIndex);
        const period = measure.getByTestId('period-list').getByRole('listitem').nth(periodIndex);

        await period.getByRole('button', { name: 'Supprimer' }).click();

        await this._endEditLocation(location);
    }

    /**
     * @param {Locator} location
     * @param {{ measureIndex: number }} options
     */
    async manipulateTimeSlots(location, { measureIndex }) {
        await this._beginEditLocation(location);

        const measure = location.getByTestId('measure-list').getByRole('listitem').nth(measureIndex);
        await measure.getByRole('button', { name: 'Ajouter une plage' }).click();

        const period = measure.getByTestId('period-list').getByRole('listitem').nth(0);
        const timeSlots = period.getByTestId('timeslot-list').getByRole('listitem');
        const addTimeSlotBtn = period.getByRole('button', { name: 'Définir des horaires' });

        await expect(timeSlots).toHaveCount(0);
        await expect(addTimeSlotBtn).toBeVisible();

        await addTimeSlotBtn.click();
        await expect(timeSlots).toHaveCount(1);
        await expect(addTimeSlotBtn).not.toBeVisible();

        await timeSlots.nth(0).getByRole('button', {name: 'Supprimer'}).click();
        await expect(timeSlots).toHaveCount(0);
        await expect(addTimeSlotBtn).toBeVisible();

        await this.cancelLocation(location);
    }

    /**
     * @param {Locator} location
     */
    async _waitForReadMode(location) {
        await location.getByRole('button', { name: 'Modifier' }).waitFor();
    }

    async reset() {
        for (const title of this.addedMeasuresTitles) {
            const location = this.getMeasureByTitle(title);
            await this._waitForReadMode(location);
            await location.getByRole('button', { name: 'Supprimer' }).click();
            await this.page.getByRole('dialog', { name: 'Supprimer cette mesure ?' }).getByRole('button', { name: 'Supprimer', exact: true }).click();
        }
    }

    async delete() {
        await this.page.getByRole('complementary').getByRole('button', { name: 'Supprimer'}).click();
        await this.page.getByRole('dialog', { name: 'Supprimer cet arrêté ?' }).getByRole('button', { name: 'Supprimer', exact: true }).click();
    }
}
