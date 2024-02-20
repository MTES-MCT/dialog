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
        }).nth(0);
    }

    /**
     * @param {number} index
     * @returns Locator
     */
    getMeasureByIndex(index) {
        return this._measures.locator('> li').nth(index);
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

        await this.page.getByTestId(/allVehicles-\d+-yes/i).click();

        // Advanced vehicles options are not shown
        await expect(this.page.getByLabel('Types de véhicules concernés', { exact: true })).not.toBeVisible(); // Restricted
        await expect(this.page.getByLabel('Indiquez les exceptions à la restriction', { exact: true })).not.toBeVisible(); // Exempted

        await this.page.getByLabel('Ville ou commune').fill(cityLabel);
        await this.page.getByRole('listbox', {name: 'Noms de communes suggérés'}).getByRole('option').first().click();
        await this.page.getByRole('textbox', {name: 'Voie'}).fill(roadName);

        await this.saveBtn.click();

        this.addedMeasuresTitles.push(restrictionType);
        return this.getMeasureByIndex(expectedIndex);
    }

    /**
     * @param {Locator} measure
     */
    async _beginEditMeasure(measure) {
        await measure.getByRole('button', { name: 'Modifier' }).click();
    }

    /**
     * @param {Locator} measure
     */
    async _endEditMeasure(measure) {
        await this.saveBtn.click();
        await this._waitForReadMode(measure);
    }

    /**
     * @param {Locator} measure
     * @param {{
     *   restrictedVehicleTypes: string[],
     *   otherRestrictedVehicleType: string,
     *   exemptedVehicleTypes: string[],
     *   otherExemptedVehicleType: string,
     * }} options
     */
    async setVehiclesOnMeasureAndAssertChangesWereSaved(measure, {
        restrictedVehicleTypes,
        otherRestrictedVehicleType,
        exemptedVehicleTypes,
        otherExemptedVehicleType,
    }) {
        await this._beginEditMeasure(measure);

        // Define restricted vehicles
        const restrictedVehiclesFieldset = measure.getByRole('radiogroup', { name: 'Véhicules concernés' });
        await restrictedVehiclesFieldset.getByTestId(/allVehicles-\d+-no/g).click();
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

        await this._endEditMeasure(measure);

        // Check changes were saved by inspecting the edit view
        await this._beginEditMeasure(measure);
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
        await this.cancelMeasure(measure);
    }

    /**
     * @param {Locator} measure
     * @param {{ days: string[], startDate: string, startTime: string[], endDate: string, endTime: string[], dayOption: string }} options
     */
    async addPeriodToMeasure(measure, { days, startDate, startTime, endDate, endTime, dayOption }) {
        await this._beginEditMeasure(measure);

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

        await this._endEditMeasure(measure);
    }

    /**
     * @param {Locator} measure
     * @param {{ periodIndex: number}} options
     */
    async removePeriodFromMeasure(measure, { periodIndex }) {
        await this._beginEditMeasure(measure);

        const period = measure.getByTestId('period-list').getByRole('listitem').nth(periodIndex);

        await period.getByRole('button', { name: 'Supprimer' }).click();

        await this._endEditMeasure(measure);
    }

    /**
     * @param {Locator} measure
     */
    async manipulateTimeSlots(measure) {
        await this._beginEditMeasure(measure);

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

        await this.cancelMeasure(measure);
    }

    /**
     * @param {Locator} measure
     */
    async _waitForReadMode(measure) {
        await measure.getByRole('button', { name: 'Modifier' }).waitFor();
    }

    async reset() {
        for (const title of this.addedMeasuresTitles) {
            const measure = this.getMeasureByTitle(title);
            await this._waitForReadMode(measure);
            await measure.getByRole('button', { name: 'Supprimer' }).click();
            await this.page.getByRole('dialog', { name: 'Supprimer cette mesure ?' }).getByRole('button', { name: 'Supprimer', exact: true }).click();
        }
    }

    async delete() {
        await this.page.getByRole('complementary').getByRole('button', { name: 'Supprimer'}).click();
        await this.page.getByRole('dialog', { name: 'Supprimer cet arrêté ?' }).getByRole('button', { name: 'Supprimer', exact: true }).click();
    }
}
