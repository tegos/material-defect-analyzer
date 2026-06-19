/**
 * Hero clip recorder for material-defect-analyzer.
 *
 * Connects to running Docker app (localhost:8080), drives the UI with
 * Playwright, records a webm via Chromium's built-in video, then
 * composites with Remotion: branded intro, Ukrainian captions, outro.
 *
 * Usage:
 *   node capture.mjs
 *
 * Env:
 *   CAPTURE_HEADED=1       watch live
 *   CAPTURE_BASE=http://…  override app URL (default http://localhost:8080)
 *   CAPTURE_RESULTS=/image/21  results page path (default /image/21)
 *   CAPTURE_TRIM_START=3   seconds to trim from start (default 3.0)
 */

import {
    launchBrowser,
    ensureCleanDir,
    findAndRenameWebm,
    sleep,
    scrollToSection,
    renderComposition,
} from '/mnt/d/work/projects/screen-capture/index.mjs';
import { mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '../..');
const OUT  = resolve(__dirname, 'out');
const DEST = resolve(ROOT, 'public/video');

const BASE         = process.env.CAPTURE_BASE        || 'http://localhost:8080';
const RESULTS_PATH = process.env.CAPTURE_RESULTS     || '/image/21';
const TRIM         = process.env.CAPTURE_TRIM_START  || '3.0';

const VIEWPORT = { width: 1280, height: 720 };

const CAPTIONS = [
    { t: 0.5,  dur: 3.0, text: 'Зображення матеріалу та сегменти' },
    { t: 4.0,  dur: 3.0, text: 'Аналіз інтенсивності' },
    { t: 7.5,  dur: 3.0, text: 'Матриця відстаней між графіками' },
    { t: 11.0, dur: 3.0, text: 'Виділені підгрупи' },
    { t: 14.5, dur: 3.0, text: 'Графіки груп' },
    { t: 18.0, dur: 3.0, text: 'Виділення груп з дефектами' },
];

async function main() {
    await ensureCleanDir(OUT);

    const { browser, context, page } = await launchBrowser({
        viewport: VIEWPORT,
        outDir: OUT,
        headed: !!process.env.CAPTURE_HEADED,
    });

    console.log('Scene: results page...');
    await page.goto(`${BASE}${RESULTS_PATH}`, { waitUntil: 'networkidle' });
    await page.evaluate(() => {
        const sb = document.getElementById('sidebar');
        if (sb) sb.style.display = 'none';
    });
    await scrollToSection(page, '#section-0');
    await sleep(1500);

    const sections = ['#section-0', '#section-1', '#section-2', '#section-3', '#section-4', '#section-5'];
    for (const sel of sections) {
        console.log(`  scrolling to ${sel}...`);
        await scrollToSection(page, sel);
        await sleep(3000);
    }
    await sleep(1000);

    console.log('Closing browser...');
    await context.close();
    await browser.close();

    const webm = resolve(OUT, 'hero-raw.webm');
    await findAndRenameWebm(OUT, webm);
    console.log('Recorded:', webm);

    const mp4 = resolve(DEST, 'hero.mp4');
    await mkdir(DEST, { recursive: true });

    console.log('\nCompositing with Remotion...');
    await renderComposition(webm, mp4, {
        title: 'Material Defect Analyzer',
        tags: ['Laravel', 'Highcharts', 'Canvas'],
        github: 'tegos/material-defect-analyzer',
        trimSeconds: parseFloat(TRIM),
        captions: CAPTIONS,
    });
    console.log('Done:', mp4);
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
