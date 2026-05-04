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
    const t0 = Date.now();
    const lap = (label) => process.stderr.write(`[render-regulation-map] ${String(Date.now() - t0).padStart(5, ' ')}ms — ${label}\n`);

    const input = JSON.parse(await readStdin());
    const { url, bounds, width, height, timeoutMs = 15000 } = input;
    lap('stdin parsed');

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    lap('chromium launched');

    try {
        const context = await browser.newContext({
            viewport: { width, height },
            deviceScaleFactor: 1,
        });
        const page = await context.newPage();
        lap('context + page created');

        page.on('pageerror', (err) => process.stderr.write(`page error: ${err.message}\n`));
        page.on('requestfailed', (req) => process.stderr.write(`request failed: ${req.url()} ${req.failure()?.errorText}\n`));

        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: timeoutMs });
        lap('page.goto domcontentloaded');

        // Wait until the d-map element has a `.map` instance (set by MapLibre once the style is loaded),
        // then fit the bounds and wait for the next idle event (i.e., all tiles for the new viewport have rendered).
        const evalTimings = await page.evaluate(async ({ bounds, timeoutMs }) => {
            const ts = [];
            const stamp = (label) => ts.push({ label, t: performance.now() });
            stamp('eval start');

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
            stamp('d-map.map ready');

            map.fitBounds(bounds, { padding: 40, animate: false, duration: 0 });
            stamp('fitBounds called');

            await new Promise((resolve, reject) => {
                const timer = setTimeout(() => reject(new Error('timeout waiting for map idle')), timeoutMs);
                map.once('idle', () => { clearTimeout(timer); resolve(); });
            });
            stamp('map idle');

            return ts;
        }, { bounds, timeoutMs });

        for (let i = 0; i < evalTimings.length; i++) {
            const prev = i === 0 ? evalTimings[0].t : evalTimings[i - 1].t;
            const delta = Math.round(evalTimings[i].t - prev);
            process.stderr.write(`[render-regulation-map]   eval +${String(delta).padStart(5, ' ')}ms — ${evalTimings[i].label}\n`);
        }
        lap('evaluate finished (maplibre + fit + idle)');

        const buffer = await page.screenshot({ type: 'jpeg', quality: 80, fullPage: false });
        lap('screenshot taken');
        process.stdout.write(buffer);
    } finally {
        await browser.close();
        lap('browser closed');
    }
})().catch((err) => {
    process.stderr.write(`render-regulation-map: ${err.stack || err.message}\n`);
    process.exit(1);
});
