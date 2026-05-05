#!/usr/bin/env node

// Render a regulation map by reusing the same MapLibre code as the web map (assets/customElements/map.js).
// PHP feeds JSON over stdin: { url, bounds, width, height, timeoutMs }.
// JPEG bytes are written to stdout. Logs go to stderr.

const { chromium } = require('playwright');

async function readStdin() {
    return new Promise((resolve, reject) => {
        let data = '';
        process.stdin.setEncoding('utf8');
        process.stdin.on('data', (chunk) => { data += chunk; });
        process.stdin.on('end', () => resolve(data));
        process.stdin.on('error', reject);
    });
}

(async () => {
    const input = JSON.parse(await readStdin());
    const { url, bounds, width, height, timeoutMs = 15000 } = input;

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    try {
        const context = await browser.newContext({
            viewport: { width, height },
            deviceScaleFactor: 1,
        });
        const page = await context.newPage();

        page.on('pageerror', (err) => process.stderr.write(`page error: ${err.message}\n`));
        page.on('requestfailed', (req) => process.stderr.write(`request failed: ${req.url()} ${req.failure()?.errorText}\n`));

        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: timeoutMs });

        // Wait until the d-map element has a `.map` instance (set by MapLibre once the style is loaded),
        // then fit the bounds and wait for the next idle event (i.e., all tiles for the new viewport have rendered).
        await page.evaluate(async ({ bounds, timeoutMs }) => {
            const dMap = document.querySelector('d-map');
            if (!dMap) throw new Error('d-map element not found');

            const waitForMap = () => new Promise((resolve, reject) => {
                const start = Date.now();
                const check = () => {
                    if (dMap.map) return resolve(dMap.map);
                    if (Date.now() - start > timeoutMs) return reject(new Error('timeout waiting for d-map.map'));
                    setTimeout(check, 50);
                };
                check();
            });

            const map = await waitForMap();
            map.fitBounds(bounds, { padding: 40, animate: false, duration: 0 });

            await new Promise((resolve, reject) => {
                const timer = setTimeout(() => reject(new Error('timeout waiting for map idle')), timeoutMs);
                map.once('idle', () => { clearTimeout(timer); resolve(); });
            });
        }, { bounds, timeoutMs });

        const buffer = await page.screenshot({ type: 'jpeg', quality: 80, fullPage: false });
        process.stdout.write(buffer);
    } finally {
        await browser.close();
    }
})().catch((err) => {
    process.stderr.write(`render-regulation-map: ${err.stack || err.message}\n`);
    process.exit(1);
});
