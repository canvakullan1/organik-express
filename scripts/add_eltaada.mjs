/**
 * Elta Ada urunlerini ekoorganik.com marka sayfasindan (ad + FIYAT + gorsel) ceker.
 * Cikti: database/data/catalog2/elta-ada-eko.json + gorseller repoya.
 *   node scripts/add_eltaada.mjs
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
const URL0 = 'https://www.ekoorganik.com/kategori/markalar/elta_ada.aspx';

const TR = { 'ç':'c','Ç':'c','ğ':'g','Ğ':'g','ı':'i','İ':'i','ö':'o','Ö':'o','ş':'s','Ş':'s','ü':'u','Ü':'u','â':'a','î':'i','û':'u' };
const slugify = s => String(s).replace(/[çÇğĞıİöÖşŞüÜâîû]/g, c => TR[c] || c)
  .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const dec = s => String(s || '').replace(/&amp;/g,'&').replace(/&quot;/g,'"').replace(/&#0?39;/g,"'")
  .replace(/&#(\d+);/g, (_, d) => String.fromCharCode(+d)).replace(/&nbsp;/g,' ').replace(/\s{2,}/g,' ').trim();

function category(name) {
  const t = name.toLowerCase();
  if (/zeytinyağ|zeytinyag|ladolia|sızma|sizma/.test(t)) return 'zeytin-zeytinyagi-yag';
  if (/dana|kıyma|kiyma|kuşbaşı|kusbasi|sucuk|köfte|kofte|hamburger|kuzu|et\b/.test(t)) return 'et-sarkuteri';
  return 'sut-urunleri';
}

const r = await fetch(URL0, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(40000) });
const t = await r.text();

// Kartlari bloklara bol: her blokta link + img + ad + fiyat birlikte
const blocks = t.split(/<div class="productGridItem/i).slice(1);
const items = [];
for (const b of blocks) {
  const name = dec((b.match(/bel3_\d+"[^>]*>([^<]+)<\/span>/i) || [])[1] || '');
  const priceRaw = (b.match(/class="GridProductPrice"[^>]*>([^<]+)</i) || [])[1] || '';
  const price = parseFloat(dec(priceRaw).replace(/[^\d.,]/g, '').replace(',', '.'));
  const img = (b.match(/<img[^>]+src=['"]([^'"]*\/images\/products\/[^'"]+)['"]/i) || [])[1] || null;
  const link = (b.match(/<a[^>]+href=['"]([^'"]*\.aspx[^'"]*)['"]/i) || [])[1] || null;
  if (name && price > 0) items.push({ name, price, img, link });
}

// Gorseli olmayanlari detay sayfasindan tamamla (og:image)
for (const it of items) {
  if (it.img || !it.link) continue;
  try {
    const dr = await fetch(it.link, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
    const dt = await dr.text();
    it.img = (dt.match(/<meta[^>]+property=['"]og:image['"][^>]+content=['"]([^'"]+)/i) || [])[1]
          || (dt.match(/<img[^>]+src=['"]([^'"]*\/images\/products\/[^'"]+)['"]/i) || [])[1] || null;
  } catch {}
}

console.log(`${items.length} urun (gorselli: ${items.filter(x=>x.img).length})`);

// gorselleri indir
let ok = 0, fail = 0;
const products = [];
for (const it of items) {
  const slug = slugify(it.name);
  const dest = join(IMG, `${slug}-1.jpg`);
  if (it.img && !existsSync(dest)) {
    try {
      const ir = await fetch(it.img, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(40000) });
      if (!ir.ok) throw new Error('http ' + ir.status);
      const buf = Buffer.from(await ir.arrayBuffer());
      const tmp = dest + '.tmp';
      writeFileSync(tmp, buf);
      await exec('magick', [tmp, '-background','white','-flatten','-resize','800x800>','-quality','82', dest]);
      unlinkSync(tmp);
      if (!existsSync(dest) || statSync(dest).size < 300) throw new Error('bos');
      ok++;
    } catch (e) { fail++; console.log('  GORSEL HATA:', slug, String(e).slice(0,40)); }
  } else if (existsSync(dest)) ok++;

  const kg = /\(KG\)/i.test(it.name);
  const cat = category(it.name);
  const sd = `${it.name}, Gökçeada'daki Elta-Ada organik çiftliğinden; soğuk zincirle özenle kapınıza gelir${kg ? '. Fiyat 1 kg içindir' : ''}.`;
  products.push({
    slug, name: it.name, category: cat, sku: null,
    price: Math.round(it.price * 100) / 100,
    unit: kg ? 'kg' : 'adet', unit_amount: 1, is_weight_based: kg,
    images: existsSync(dest) ? [`products/${slug}-1.jpg`] : [],
    short_description: sd,
    description: `<p><strong>${it.name}</strong>, Gökçeada'daki Elta-Ada organik çiftliğinde sertifikalı organik tarım ve hayvan refahı ilkeleriyle üretilir.</p>` +
      `<ul><li>Sertifikalı organik üretim</li><li>Çiftlikten sofraya, soğuk zincirle</li><li>Katkısız ve doğal içerik</li>${kg ? '<li>Fiyat 1 kg içindir</li>' : ''}</ul>`,
    meta_title: `${it.name} | Elta-Ada Organik - Organik Express`,
    meta_description: sd,
  });
}
console.log(`gorsel: ${ok} hazir, ${fail} hata`);

writeFileSync(join(ROOT, 'database/data/catalog2/elta-ada-eko.json'),
  JSON.stringify({ source: 'elta-ada-eko', scraped_at: new Date().toISOString(), products }, null, 2), 'utf8');

const cats = {};
products.forEach(p => cats[p.category] = (cats[p.category] || 0) + 1);
console.log('elta-ada-eko:', products.length, 'urun ->', JSON.stringify(cats));
products.forEach(p => console.log(`  ${String(p.price).padStart(6)} TL ${p.category.padEnd(22)} ${p.name}${p.images.length?'':'  [GORSELSIZ]'}`));
