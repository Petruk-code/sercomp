import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Resvg } from '@resvg/resvg-js';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = join(root, 'public', 'icons');

const favicon = readFileSync(join(root, 'public', 'favicon.svg'), 'utf8');
const inner = favicon.replace(/^<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '');

mkdirSync(outDir, { recursive: true });

function render(svg, size, filename) {
    const resvg = new Resvg(svg, { fitTo: { mode: 'width', value: size } });
    writeFileSync(join(outDir, filename), resvg.render().asPng());
}

const maskableScale = (0.66 * 512) / 166;
const maskableOffset = (512 - 166 * maskableScale) / 2;
const maskableSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g transform="translate(${maskableOffset} ${maskableOffset}) scale(${maskableScale})">${inner}</g></svg>`;

render(favicon, 192, 'icon-192.png');
render(favicon, 512, 'icon-512.png');
render(maskableSvg, 512, 'maskable-512.png');
render(favicon, 180, 'apple-touch-icon.png');

console.log('PWA icons generated in public/icons/');
