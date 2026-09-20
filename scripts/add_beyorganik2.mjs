/**
 * beyorganik.com (Ticimax) -> database/data/catalog2/beyorganik.json (MEVCUT dosyaya EKLER)
 *
 * ÖNEMLİ: database/data/catalog2/beyorganik.json'da Temmuz 2026'dan kalma 67 ürün zaten
 * VAR ve canlıda güncel fiyatlı — bunlara DOKUNULMAZ, sadece SKU/isim eşleşmeyen YENİ
 * ürünler eklenir (updateOrCreate slug'a göre çalıştığı için slug çakışması da ayrıca
 * kontrol edilir).
 *
 *   node scripts/add_beyorganik2.mjs
 */
import { writeFileSync, existsSync, mkdirSync, unlinkSync, statSync, readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const exec = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const IMG = join(ROOT, 'storage', 'app', 'public', 'products');
const CATALOG_FILE = join(ROOT, 'database/data/catalog2/beyorganik.json');
mkdirSync(IMG, { recursive: true });
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36' };

const TR = { 'ç': 'c', 'Ç': 'c', 'ğ': 'g', 'Ğ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'Ö': 'o', 'ş': 's', 'Ş': 's', 'ü': 'u', 'Ü': 'u', 'â': 'a', 'î': 'i', 'û': 'u' };
const slugify = (s) => String(s).replace(/[çÇğĞıİöÖşŞüÜâîû]/g, (c) => TR[c] || c)
  .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const norm = (s) => String(s).toLowerCase()
  .replace(/[çÇğĞıİöÖşŞüÜâîû]/g, (c) => TR[c] || c)
  .replace(/[^a-z0-9]+/g, ' ').trim();

async function get(u, tries = 2) {
  for (let i = 0; i < tries; i++) {
    try {
      const r = await fetch(u, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
      if (r.ok) return await r.text();
    } catch {}
  }
  return '';
}

async function pool(items, fn, conc = 6) {
  const out = [];
  let i = 0, done = 0;
  await Promise.all(Array.from({ length: Math.min(conc, items.length) }, async () => {
    while (i < items.length) {
      const k = i++;
      out[k] = await fn(items[k]);
      if (++done % 20 === 0) process.stderr.write(`  ${done}/${items.length}\r`);
    }
  }));
  process.stderr.write(`  ${done}/${items.length}\n`);
  return out;
}

/** Ürün adından bizim prod kategorimize eşleme (itirorganik.mjs ile aynı, et-sarkuteri
 *  -> et-tavuk düzeltmesiyle; o kategori 2026-09-20'de et-tavuk'a birleştirildi/gizlendi). */
function mapCategory(name) {
  // NOT: JS'te 'İ'.toLowerCase() -> 'i' + BİRLEŞTİRİCİ NOKTA (U+0307) verir, düz "i" değil —
  // bu da 'İrmik' gibi kelimelerin 'irmik' anahtar kelimesiyle eşleşmesini SESSİZCE engelliyordu.
  const t = name.toLowerCase().replace(/̇/g, '');
  const has = (...k) => k.some((x) => t.includes(x));
  const word = (...k) => k.some((x) => new RegExp(`(^|[^a-z0-9çğıöşü])${x}([^a-z0-9çğıöşü]|$)`, 'i').test(t));

  // Gıda olmayan ürünler (çanta vb.) — hiçbir kategoriye sokma, atla.
  if (has('bez çanta', 'tişört', 'tisort', 'termos', 'matara')) return null;

  if (has('sabun', 'şampuan', 'sampuan', 'deterjan', 'temizl', 'diş macunu', 'dis macunu',
    'roll-on', 'roll on', 'deodorant', 'krem', 'losyon', 'bakım', 'bakim', 'hijyen',
    'peçete', 'pecete', 'bulaşık', 'bulasik', 'çamaşır', 'camasir', 'yüzey', 'yuzey',
    'aloe vera', 'jel', 'sprey', 'diş fırça', 'dis firca', 'fırça', 'firca', 'kolonya',
    'bebek yağı', 'bebek yagi', 'bebek kremi')) return 'dogal-yasam-temizlik';
  if (has('bebek', 'ek gıda', 'ek gida')) return 'bebek';
  if (has('glutensiz')) return 'glutensiz';
  if (has('ekmek', 'ekmeğ', 'ekmeg', 'somun', 'lavaş', 'lavas', 'galeta', 'simit', 'poğaça', 'pogaca')) return 'firin-ekmek';
  if (has('mercimek', 'nohut', 'fasulye', 'bulgur', 'pirinç', 'pirinc', 'makarna', 'erişte', 'eriste',
    'bakliyat', 'bezelye', 'barbunya', 'buğday', 'bugday', 'kinoa', 'şehriye', 'sehriye', 'yulaf',
    'irmik', 'nişasta', 'nisasta', 'mısır', 'misir', 'tarhana') || word('un', 'unu')) return 'bakliyat-makarna';
  if (has('baharat', 'kekik', 'nane', 'kimyon', 'zerdeçal', 'zerdecal', 'tarçın', 'tarcin',
    'karabiber', 'pul biber', 'toz biber', 'zencefil', 'sumak', 'çörek otu', 'corek otu',
    'anason', 'rezene', 'defne', 'kişniş', 'kisnis', 'yenibahar', 'safran', 'çemen', 'tuz', 'biberiye',
    'karbonat')) return 'baharat-aktar';
  if (has('yumurta')) return 'yumurta';
  // "Hindistan cevizi" (hindistan cevizi YAĞI dahil) 'ceviz' alt-dizesini içerdiği için
  // kuruyemiş kontrolünden ÖNCE burada özellikle yakalanmalı — yoksa yanlışlıkla
  // "kuruyemiş" kategorisine düşer (ceviz=walnut ile yanlış eşleşme).
  if (has('zeytinyağ', 'zeytinyag', 'sızma', 'sizma', 'zeytin', 'ayçiçek yağ', 'aycicek yag',
    'hindistan cevizi yağ', 'hindistan cevizi yag')) return 'zeytin-zeytinyagi-yag';
  if (has('süt', 'yoğurt', 'yogurt', 'peynir', 'kefir', 'tereyağ', 'tereyag', 'kaymak', 'ayran', 'çökelek') || word('sut', 'lor')) return 'sut-urunleri';
  if (has('sucuk', 'kıyma', 'kiyma', 'kuşbaşı', 'kusbasi', 'pastırma', 'pastirma', 'salam', 'tavuk', 'kavurma') || word('et', 'dana', 'kuzu')) return 'et-tavuk';
  if (word('bal', 'balı', 'bali', 'ballar') || has('karakovan', 'petek bal', 'süzme bal', 'suzme bal')) return 'bal';
  if (has('reçel', 'recel', 'marmelat', 'pekmez', 'tahin', 'kahvalt', 'helva', 'granola')) return 'kahvaltilik-recel';
  if (has('sirke', 'salça', 'salca', 'ketçap', 'ketchup', 'sosu', 'soslar', 'püre', 'pure', 'ekşi', 'eksi', 'turşu', 'tursu', 'konserve') || word('sos')) return 'sos-salca-sirke';
  if (has('çikolata', 'cikolata', 'bisküvi', 'biskuvi', 'kurabiye', 'lokum', 'gofret', 'tatlı', 'cips', 'kraker', 'muhallebi', 'puding')) return 'tatli-cikolata';
  if (has('ceviz', 'badem', 'fındık', 'findik', 'fıstık', 'fistik', 'üzüm', 'uzum', 'kayısı', 'kayisi',
    'incir', 'hurma', 'leblebi', 'çekirde', 'kuruyemiş', 'kuruyemis', 'tohum', 'kuru ',
    'fıstığ', 'fistig', 'fındığ', 'findig', 'cevizi')) return 'kuruyemis-kurutulmus';
  if (has('kahve', 'çay', 'cayi', 'içecek', 'icecek', 'limonata', 'şurup', 'surup', 'konsantre',
    'espresso', 'melisa', 'ekinezya', 'ıhlamur', 'ihlamur', 'papatya', 'adaçay', 'adacay', 'çiçeği', 'cicegi',
    'shot', 'tonik', 'tonic', 'özü', 'ozu', 'meyve suyu', ' suyu', 'kozalak', 'kuzukulağı', 'kuzukulagi', 'kekre')) return 'icecek-cay';
  if (has('corba', 'çorba', 'kuskus', 'pankek', 'krep', 'irmik', 'tahıl karışımı', 'tahil karisimi',
    'tahıl topları', 'tahil toplari', 'köfte harcı', 'kofte harci', 'köfte harc')) return 'bakliyat-makarna';
  if (has('macunu', 'kış macunu', 'kis macunu')) return 'kahvaltilik-recel';
  // Beyorganik'e özgü ürün ailesi adları (BEY-ATOM, BEYÖZ, BEY-NIGHT/MOM'S/MOR/ARONYA Mix vb.)
  // hepsi kuruyemiş+meyve tabanlı enerji/tohum karışımları.
  if (has('bey-atom', 'bey atom', 'beyöz', 'beyoz', 'bey-night', 'bey night', "mom's mix", 'moms mix',
    'mor mix', 'aronya mix', 'bey mor', 'karışık kinoa')) return 'kuruyemis-kurutulmus';
  return null;
}

function decodeJsonLdString(html) {
  const m = html.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/);
  if (!m) return null;
  try { return JSON.parse(m[1]); } catch { return null; }
}

function cleanDescription(desc) {
  if (!desc) return '';
  // Kendi kargo/ambalaj süreçlerini anlatan bölümü çıkar (bizim lojistiğimizle ilgisi yok)
  let d = desc.replace(/🚚 Güvenli Kargo[\s\S]*?elinize ulaşır\.\s*/i, '');
  const paras = d.split(/\r?\n\r?\n+/).map((p) => p.trim()).filter(Boolean);
  return paras.map((p) => `<p>${p.replace(/\r?\n/g, '<br>')}</p>`).join('\n');
}

// ── 1) Mevcut katalogdan SKU + normalize-isim seti çıkar (dokunulmayacaklar) ──
const existing = JSON.parse(readFileSync(CATALOG_FILE, 'utf8'));
const existingSkus = new Set(existing.products.map((p) => p.sku).filter(Boolean));
const existingSlugs = new Set(existing.products.map((p) => p.slug));
const existingNames = new Set(existing.products.map((p) => norm(p.name)));
console.error(`Mevcut beyorganik.json: ${existing.products.length} ürün (dokunulmayacak)`);

// ── 2) Sitemap'ten tüm ürün URL'lerini al ──
const sm = await get('https://www.beyorganik.com/sitemap/products/0.xml');
const urls = [...new Set([...sm.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)].map((m) => m[1]))];
console.error(`${urls.length} ürün sayfası bulundu, indiriliyor...`);

// ── 3) Sayfalardan JSON-LD çek ──
const rows = await pool(urls, async (u) => {
  const html = await get(u);
  if (!html) return null;
  const ld = decodeJsonLdString(html);
  if (!ld || !ld.name) return null;
  const name = ld.name.replace(/\s{2,}/g, ' ').trim();
  const price = parseFloat(ld.offers?.price || '0') || 0;
  const sku = ld.sku || null;
  const images = Array.isArray(ld.image) ? ld.image : (ld.image ? [ld.image] : []);
  return { url: u, name, price, sku, images, description: ld.description || '' };
}, 6);

const items = rows.filter(Boolean);
console.error(`${items.length} üründe veri bulundu`);

// ── 4) Zaten var olanları ele (SKU ya da isim eşleşirse dokunma) ──
const skipped = [];
const fresh = items.filter((it) => {
  if (it.sku && existingSkus.has(it.sku)) { skipped.push(it.name + ' [sku eşleşti]'); return false; }
  if (existingNames.has(norm(it.name))) { skipped.push(it.name + ' [isim eşleşti]'); return false; }
  if (it.price <= 0) { skipped.push(it.name + ' [fiyat yok]'); return false; }
  return true;
});
console.error(`${fresh.length} YENİ ürün, ${skipped.length} atlandı (zaten var / fiyatsız)`);

// ── 5) Yeni ürünleri işle: kategori + görsel indir + JSON satırı ──
let ok = 0, fail = 0;
const noCat = [];
const newProducts = [];
const seenSlugs = new Set(existingSlugs);

for (const it of fresh) {
  const cat = mapCategory(it.name);
  if (!cat) { noCat.push(it.name); continue; }
  let slug = slugify(it.name);
  if (!slug) continue;
  // slug çakışması olursa (mevcut 67'den biriyle ya da bu partide) benzersizleştir
  let base = slug, n = 2;
  while (seenSlugs.has(slug)) { slug = `${base}-${n++}`; }
  seenSlugs.add(slug);

  const dest = join(IMG, `${slug}-1.jpg`);
  if (it.images[0] && !existsSync(dest)) {
    try {
      const r = await fetch(it.images[0], { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(40000) });
      if (!r.ok) throw new Error('http ' + r.status);
      const buf = Buffer.from(await r.arrayBuffer());
      const tmp = dest + '.tmp';
      writeFileSync(tmp, buf);
      await exec('magick', [tmp, '-background', 'white', '-flatten', '-resize', '1200x1200>', '-quality', '85', dest]);
      unlinkSync(tmp);
      if (!existsSync(dest) || statSync(dest).size < 300) throw new Error('bos');
      ok++;
    } catch { fail++; }
  } else if (existsSync(dest)) ok++;

  const desc = cleanDescription(it.description);
  const shortDesc = it.description
    ? it.description.split(/\r?\n\r?\n/)[0].replace(/\r?\n/g, ' ').trim().slice(0, 220)
    : `${it.name}, BEYORGANİK güvencesiyle Organik Express rafında.`;

  newProducts.push({
    slug,
    name: it.name,
    category: cat,
    sku: it.sku,
    price: Math.round(it.price * 100) / 100,
    unit: 'adet',
    unit_amount: 1,
    is_weight_based: false,
    images: existsSync(dest) ? [`products/${slug}-1.jpg`] : [],
    short_description: shortDesc,
    description: desc,
    meta_title: `${it.name} | Organik Express`,
    meta_description: shortDesc,
  });
}

existing.products.push(...newProducts);
existing.scraped_at = new Date().toISOString();
writeFileSync(CATALOG_FILE, JSON.stringify(existing, null, 2), 'utf8');

const cats = {};
newProducts.forEach((p) => { cats[p.category] = (cats[p.category] || 0) + 1; });
console.log(`\nbeyorganik: ${newProducts.length} YENİ ürün eklendi (toplam artık ${existing.products.length}) | görsel ${ok} ok / ${fail} hata`);
Object.entries(cats).sort((a, b) => b[1] - a[1]).forEach(([k, v]) => console.log(`  ${k.padEnd(24)} ${v}`));
if (noCat.length) {
  console.log(`\nKATEGORİSİZ (${noCat.length}) — atlandı:`);
  noCat.forEach((n) => console.log('  ' + n));
}
if (skipped.length) {
  console.log(`\nATLANDI - zaten mevcut / fiyatsız (${skipped.length}):`);
  skipped.forEach((n) => console.log('  ' + n));
}
