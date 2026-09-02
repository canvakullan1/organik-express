/**
 * Musterinin verdigi 8 taze urunu olusturur (fiyatlar musteriden),
 * gorselleri tazedukkan.com.tr'den indirip 800px'e kuculterek repoya yazar.
 *   node scripts/add_tazedukkan.mjs
 */
import { writeFileSync, existsSync, mkdirSync, unlinkSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
const exec = promisify(execFile);

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const IMG = join(ROOT, 'storage', 'app', 'public', 'products');
mkdirSync(IMG, { recursive: true });
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' };

// slug -> [ad, kategori, fiyat, birim, miktar, agirlikMi, gorselURL]
const CDN = 'https://static.ticimax.cloud/cdn-cgi/image/width=-,quality=99/56277/uploads/urunresimleri/buyuk/';
const items = [
  ['seftali', 'Şeftali', 'taze-meyve', 400, 'kg', 1, true, CDN + 'seftali-1kgmeyve-sebze--53fb-.png'],
  ['nektarin', 'Nektarin', 'taze-meyve', 350, 'kg', 1, true, CDN + 'nektarin-1-kgmevsim-meyveleri-be16-7.png'],
  ['cekirdeksiz-uzum', 'Çekirdeksiz Üzüm', 'taze-meyve', 400, 'kg', 1, true, CDN + 'cekirdeksiz-uzum-1-kgmevsim-meyveleri-0-bca8.png'],
  ['taze-incir', 'Taze İncir', 'taze-meyve', 500, 'kg', 1, true, CDN + 'bursa-siyah-incir-1kgmeyve-sebze-496-aa.png'],
  ['bogurtlen-250-g', 'Böğürtlen 250 g', 'taze-meyve', 300, 'paket', 1, false, CDN + 'taze-bogurtlenyaban-mersini-ve-orman-m-832d23.png'],
  ['ahududu-250-g', 'Ahududu 250 g', 'taze-meyve', 300, 'paket', 1, false, CDN + 'taze-premium-frambuaz-ahududu-125-gyab-b88d-2.png'],
  ['salcalik-domates', 'Salçalık Domates', 'taze-sebze', 80, 'kg', 1, true, CDN + 'organik-rio-salcalik-domates-10-kg-sad-99e7-2.png'],
  ['salcalik-domates-10-kg', 'Salçalık Domates 10 kg Kasa', 'taze-sebze', 750, 'paket', 1, false, CDN + 'organik-rio-salcalik-domates-10-kg-sad-99e7-2.png'],
];

const shortDesc = (n, kg) => `${n}, mevsiminde özenle seçilip taze haliyle kapınıza gelir${kg ? '; kilogram fiyatıdır' : ''}.`;
const longDesc = (n, kg) =>
  `<p><strong>${n}</strong>, hasat sonrası özenle ayıklanır ve tazeliğini koruyacak şekilde paketlenerek gönderilir. ` +
  `Sofranıza mevsimin en taze halini getirmek için seçilmiştir.</p>` +
  `<ul><li>Mevsiminde taze hasat</li><li>Özenle ayıklanmış, seçilmiş ürün</li>` +
  `<li>Tazeliği koruyan özenli paketleme</li>${kg ? '<li>Fiyat 1 kg içindir</li>' : ''}</ul>`;

// 1) görseller
let ok = 0, fail = 0;
for (const [slug, , , , , , , url] of items) {
  const dest = join(IMG, `${slug}-1.jpg`);
  if (existsSync(dest)) { ok++; continue; }
  try {
    const r = await fetch(url, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(40000) });
    if (!r.ok) throw new Error('http ' + r.status);
    const buf = Buffer.from(await r.arrayBuffer());
    const tmp = dest + '.tmp';
    writeFileSync(tmp, buf);
    await exec('magick', [tmp, '-background', 'white', '-flatten', '-resize', '800x800>', '-quality', '82', dest]);
    unlinkSync(tmp);
    if (!existsSync(dest) || statSync(dest).size < 300) throw new Error('bos');
    ok++;
  } catch (e) { fail++; console.log('  GORSEL HATA:', slug, String(e).slice(0, 40)); }
}
console.log(`gorsel: ${ok} hazir, ${fail} hata`);

// 2) JSON
const products = items.map(([slug, name, category, price, unit, unit_amount, weight]) => ({
  slug, name, category, sku: null, price,
  unit, unit_amount, is_weight_based: weight,
  images: [`products/${slug}-1.jpg`],
  short_description: shortDesc(name, weight),
  description: longDesc(name, weight),
  meta_title: `${name} | Taze ${category === 'taze-meyve' ? 'Meyve' : 'Sebze'} - Organik Express`,
  meta_description: shortDesc(name, weight),
}));

writeFileSync(join(ROOT, 'database/data/catalog2/tazedukkan.json'),
  JSON.stringify({ source: 'tazedukkan', scraped_at: new Date().toISOString(), products }, null, 2), 'utf8');
console.log(`tazedukkan: ${products.length} ürün yazıldı`);
products.forEach(p => console.log(`  ${String(p.price).padStart(4)} TL  ${p.category.padEnd(12)} ${p.name}`));
