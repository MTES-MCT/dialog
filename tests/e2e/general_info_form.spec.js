// @ts-check
const { test, expect } = require('@playwright/test');

test.use({ storageState: 'playwright/.auth/mathieu.json' });

test('Edit category', async ({ page }) => {
    await page.goto('/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

    const editButton = page.getByTestId('general_info').getByRole('button', { name: 'Modifier' });

    // Check category fields...
    await editButton.click();
    const categoryField = page.getByRole('combobox', { name: "Nature de l'arrêté" });
    await expect(categoryField).toHaveValue('event');
    const otherCategoryTextField = page.getByRole('textbox', { name: "Préciser la nature de l'arrêté" });
    await expect(otherCategoryTextField).not.toBeVisible();

    // Set category to "Other"...
    await categoryField.selectOption({ label: 'Autre' });
    await expect(otherCategoryTextField).toBeVisible();
    await expect(otherCategoryTextField).toBeEmpty();
    await otherCategoryTextField.fill('Dérogation préfectorale');
    await expect(otherCategoryTextField).toHaveValue('Dérogation préfectorale');
    // Check other category text persists if we navigate options
    await categoryField.selectOption({ label: 'Travaux' });
    await categoryField.selectOption({ label: 'Autre' });
    await expect(otherCategoryTextField).toHaveValue('Dérogation préfectorale');
    const submitButton = page.getByRole('button', { name: 'Valider' });
    await submitButton.click();

    // Move back to initial category...
    /*await editButton.click();
    await categoryField.selectOption({ label: 'Évènement' });
    await submitButton.click();
    // Check other category text has been cleared
    await editButton.click();
    await expect(otherCategoryTextField).not.toBeVisible();
    await categoryField.selectOption({ label: 'Autre' });
    await expect(otherCategoryTextField).toBeEmpty();*/
});
