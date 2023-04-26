// @ts-check
const { test: setup } = require("@playwright/test");

const mathieuFile = 'playwright/.auth/mathieu.json';

setup('authenticate', async ({ page }) => {
    await page.goto("/login");
    await page.fill("input[name='email']", 'mathieu.marchois@beta.gouv.fr');
    await page.fill("input[name='password']", 'password');
    await page.getByRole('button', { name: 'Se connecter' }).click();
    await page.waitForURL("/regulations");
    await page.context().storageState({ path: mathieuFile });
});
