/**
 * nisaorganik.com.tr (OpenCart) -> database/data/catalog2/nisaorganik.json
 *
 * ÖNEMLİ (müşteri talimatı):
 *  - Nisa Organik BİZİM GİBİ bir satış sitesi (rakip) — hiçbir üründe "Nisa" marka/
 *    üretici olarak KULLANILMAZ. Her ürünün kendi JSON-LD brand.name'i (Yeşilküre,
 *    Uluova, U Green Clean, vb.) gerçek üretici olarak kaydedilir.
 *  - Organik Express'te bu sitelerden ELLE eklenmiş, GÜNCEL FİYATLI ürünlere
 *    dokunulmaz — mevcut aktif ürün adlarıyla eşleşenler atlanır.
 *  - Kategori eşlemesi Nisa'nın KENDİ kategori alanına DEĞİL (onların "Kasap &
 *    Şarküteri" gibi geniş gruplaması bizim taksonomimizle örtüşmüyor — süt bile
 *    oraya giriyor), ürün adından anahtar kelimeyle bizim taksonomimize yapılır
 *    (add_beyorganik2.mjs'deki mapCategory ile aynı yöntem, çok genişletilmiş).
 *
 *   node scripts/add_nisaorganik.mjs           # tam kazıma (uzun sürer, arka planda çalıştır)
 *   node scripts/add_nisaorganik.mjs --limit=100 --dry   # kategori/marka kalitesini test et, dosyaya yazma
 */
import { writeFileSync, existsSync, mkdirSync, unlinkSync, statSync, readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const exec = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const IMG = join(ROOT, 'storage', 'app', 'public', 'products');
const CATALOG_FILE = join(ROOT, 'database/data/catalog2/nisaorganik.json');
mkdirSync(IMG, { recursive: true });
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36' };

const args = Object.fromEntries(process.argv.slice(2).map((a) => {
  const [k, v] = a.replace(/^--/, '').split('=');
  return [k, v ?? true];
}));
const LIMIT = args.limit ? parseInt(args.limit, 10) : 0;
const DRY = !!args.dry;

const TR = { 'ç': 'c', 'Ç': 'c', 'ğ': 'g', 'Ğ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'Ö': 'o', 'ş': 's', 'Ş': 's', 'ü': 'u', 'Ü': 'u', 'â': 'a', 'î': 'i', 'û': 'u' };
const slugify = (s) => String(s).replace(/[çÇğĞıİöÖşŞüÜâîû]/g, (c) => TR[c] || c)
  .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const norm = (s) => String(s).toLowerCase().replace(/̇/g, '')
  .replace(/[çÇğĞıİöÖşŞüÜâîû]/g, (c) => TR[c] || c)
  .replace(/[^a-z0-9]+/g, ' ').trim();
const normBrand = (s) => norm(s).replace(/\s+/g, '');

async function get(u, tries = 2) {
  for (let i = 0; i < tries; i++) {
    try {
      const r = await fetch(u, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
      if (r.ok) return await r.text();
    } catch {}
  }
  return '';
}

async function pool(items, fn, conc = 10) {
  const out = [];
  let i = 0, done = 0;
  await Promise.all(Array.from({ length: Math.min(conc, items.length) }, async () => {
    while (i < items.length) {
      const k = i++;
      out[k] = await fn(items[k]);
      if (++done % 50 === 0) process.stderr.write(`  ${done}/${items.length}\r`);
    }
  }));
  process.stderr.write(`  ${done}/${items.length}\n`);
  return out;
}

/** Ürün adından bizim prod kategorimize eşleme — add_beyorganik2.mjs ile aynı taban,
 *  taze sebze/meyve/et/kişisel bakım/bebek için genişletilmiş. */
const BABY_BRANDS = new Set(['bebeklik', 'bambala'].map(normBrand));
const ESSENTIAL_OIL_BRANDS = new Set(['harmanyeri'].map(normBrand));

function mapCategory(name, brand) {
  const t = name.toLowerCase().replace(/̇/g, '');
  const has = (...k) => k.some((x) => t.includes(x));
  const word = (...k) => k.some((x) => new RegExp(`(^|[^a-z0-9çğıöşü])${x}([^a-z0-9çğıöşü]|$)`, 'i').test(t));

  if (has('bez çanta', 'tişört', 'tisort', 'termos', 'matara', 'kitap', 'hediye kart')) return null;

  // Marka bazlı bebek gıda/mama serileri (ürün adında "bebek" geçmez).
  if (BABY_BRANDS.has(normBrand(brand || ''))) return 'bebek';
  // Marka bazlı esansiyel/aromaterapi yağları (yemeklik değil, kişisel bakım).
  if (ESSENTIAL_OIL_BRANDS.has(normBrand(brand || ''))) return 'dogal-yasam-temizlik';

  // ── Kişisel bakım & temizlik ──
  if (has('sabun', 'şampuan', 'sampuan', 'deterjan', 'temizl', 'diş macunu', 'dis macunu',
    'roll-on', 'roll on', 'deodorant', 'krem', 'losyon', 'bakım', 'bakim', 'hijyen',
    'peçete', 'pecete', 'bulaşık', 'bulasik', 'çamaşır', 'camasir', 'yüzey', 'yuzey',
    'aloe vera', 'jel', 'sprey', 'diş fırça', 'dis firca', 'fırça', 'firca', 'kolonya',
    'islak mendil', 'ıslak mendil', 'mendil', 'pamuk', 'biberon', 'emzik', 'ped',
    'saç', 'sac bakim', 'vücut', 'vucut', 'duş jeli', 'dus jeli', 'parfüm', 'parfum',
    'leke çıkarıcı', 'leke cikarici', 'yumuşatıcı', 'yumusatici', 'argan yağ', 'argan yag',
    'ardıç yağ', 'ardic yag', 'terebentin', 'çay ağacı yağ', 'cay agaci yag', 'diş ipi', 'dis ipi',
    'toothpaste', 'diş fırçası', 'dis fircasi', 'bebek yağı', 'bebek yagi', 'bebek kremi',
    'bebek şampuan', 'bebek sampuan', 'bebek sabun', 'kulak çubuğu', 'kulak cubugu')) return 'dogal-yasam-temizlik';

  // ── Bebek (bakım hariç — gıda/ek gıda) ──
  if (has('bebek maması', 'bebek mamasi', 'ek gıda', 'ek gida', 'bebek bisküvi', 'bebek biskuvi',
    'bebek çorbası', 'bebek corbasi', 'bebek irmiği', 'bebek irmigi', 'bebek kaşığı', 'bebek kasigi')
    || (has('bebek') && ! has('bebek yağı', 'bebek yagi', 'bebek kremi', 'bebek şampuan', 'bebek sampuan', 'bebek sabun'))) return 'bebek';

  if (has('glutensiz')) return 'glutensiz';

  // ── Simit & Poğaça (Fırın & Ekmek'ten AYRI kategori) ──
  if (has('simit', 'poğaça', 'pogaca')) return 'simit-pogaca';
  if (has('ekmek', 'ekmeğ', 'ekmeg', 'somun', 'lavaş', 'lavas', 'galeta', 'francala', 'baget')) return 'firin-ekmek';

  if (has('mercime', 'nohut', 'fasulye', 'bulgur', 'pirinç', 'pirinc', 'makarna', 'erişte', 'eriste',
    'bakliyat', 'bezelye kuru', 'barbunya kuru', 'buğday', 'bugday', 'kinoa', 'şehriye', 'sehriye', 'yulaf',
    'irmik', 'nişasta', 'nisasta', 'mısır unu', 'misir unu', 'tarhana', 'kuskus', 'börülce', 'borulce') || word('un', 'unu')) return 'bakliyat-makarna';

  if (has('baharat', 'kekik', 'nane kuru', 'kuru nane', 'kimyon', 'zerdeçal', 'zerdecal', 'tarçın', 'tarcin',
    'karabiber', 'pul biber', 'toz biber', 'zencefil toz', 'sumak', 'çörek otu', 'corek otu',
    'anason', 'rezene', 'defne', 'kişniş', 'kisnis', 'yenibahar', 'safran', 'çemen', 'tuz', 'biberiye',
    'karbonat', 'maya', 'vanilya', 'damla sakızı', 'damla sakizi')) return 'baharat-aktar';

  if (has('yumurta')) return 'yumurta';

  if (has('zeytinyağ', 'zeytinyag', 'sızma', 'sizma', 'zeytin yağ', 'ayçiçek yağ', 'aycicek yag',
    'hindistan cevizi yağ', 'hindistan cevizi yag', 'susam yağ', 'susam yag', 'kanola', 'fındık yağ', 'findik yag')) return 'zeytin-zeytinyagi-yag';
  // "Zeytin" (yemeklik/sofralık zeytin, salamura) — yağ değilse de aynı kategoriye girer.
  if (has('zeytin')) return 'zeytin-zeytinyagi-yag';

  // ── Süt ürünleri ──
  if (has('süt', 'sut ', 'yoğur', 'yogur', 'peynir', 'kefir', 'tereyağ', 'tereyag', 'kaymak',
    'ayran', 'çökelek', 'cokelek', 'krema', 'labne', 'keçi yağ', 'keci yag', 'keçi sade yağ') || word('sut', 'lor')) return 'sut-urunleri';

  // ── Et & Tavuk (işlenmiş + taze kırmızı/beyaz et) ──
  if (has('sucuk', 'kıyma', 'kiyma', 'kuşbaşı', 'kusbasi', 'pastırma', 'pastirma', 'salam', 'sosis',
    'jambon', 'kavurma', 'biftek', 'antrikot', 'pirzola', 'kaburga', 'ciğer', 'ciger', 'sakatat',
    'but', 'göğüs', 'gogus', 'kanat', 'pilic', 'piliç', 'tavuk', 'hindi', 'bonfile')
    || word('et', 'dana', 'kuzu', 'ördek', 'ordek')) return 'et-tavuk';

  if (word('bal', 'balı', 'bali', 'ballar') || has('karakovan', 'petek bal', 'süzme bal', 'suzme bal',
    'propolis', 'arı sütü', 'ari sutu', 'polen', 'arı ürün', 'ari urun')) return 'bal';

  if (has('reçel', 'recel', 'marmelat', 'pekmez', 'tahin', 'kahvalt', 'helva', 'granola', 'müsli', 'musli', 'gevrek')) return 'kahvaltilik-recel';

  if (has('sirke', 'salça', 'salca', 'ketçap', 'ketchup', 'sosu', 'soslar', 'püre', 'pure', 'ekşi', 'eksi',
    'turşu', 'tursu', 'konserve', 'mayonez') || word('sos')) return 'sos-salca-sirke';

  if (has('çikolata', 'cikolata', 'bisküvi', 'biskuvi', 'kurabiye', 'lokum', 'gofret', 'tatlı', 'cips',
    'kraker', 'muhallebi', 'puding', 'dondurma', 'şeker', 'seker', 'pasta', 'kek', 'brownie', 'çıtır', 'citir',
    'kakao')) return 'tatli-cikolata';

  if (has('ceviz', 'badem', 'fındık', 'findik', 'fıstık', 'fistik', 'kayısı kuru', 'kuru kayısı',
    'incir kuru', 'kuru incir', 'hurma', 'leblebi', 'çekirde', 'kuruyemiş', 'kuruyemis', 'tohum',
    'kuru üzüm', 'kuru uzum', 'kuru meyve', 'chia', 'susam', 'protein karışımı', 'protein karisimi',
    'açai', 'acai')) return 'kuruyemis-kurutulmus';

  if (has('kahve', 'çay', 'cayi', 'içecek', 'icecek', 'limonata', 'şurup', 'surup', 'konsantre',
    'espresso', 'melisa', 'ekinezya', 'ıhlamur', 'ihlamur', 'papatya', 'adaçay', 'adacay', 'çiçeği', 'cicegi',
    'shot', 'tonik', 'tonic', 'özü', 'ozu', 'meyve suyu', ' suyu', 'kozalak', 'kuzukulağı', 'kuzukulagi', 'kekre',
    'kombucha', 'smoothie', 'fermente', 'kvas')) return 'icecek-cay';

  // ── Taze sebze (kuru bakliyat çıkarıldıktan sonra) ──
  if (has('domates', 'salatalık', 'salatalik', 'biber taze', 'taze biber', 'patates', 'soğan', 'sogan',
    'havuç', 'havuc', 'patlıcan', 'patlican', 'kabak', 'ıspanak', 'ispanak', 'marul', 'lahana', 'brokoli',
    'karnabahar', 'pırasa', 'pirasa', 'kereviz', 'turp', 'pancar', 'taze bakla', 'taze fasulye',
    'taze bezelye', 'mantar', 'sarımsak', 'sarimsak', 'maydanoz', 'dereotu', 'taze nane', 'roka',
    'semizotu', 'kuşkonmaz', 'kuskonmaz', 'enginar', 'kabuklu', 'yeşillik', 'yesillik', 'kızılcık', 'kizilcik',
    'sebze sepeti', 'sebze paketi', 'taze biber')) return 'taze-sebze';

  // ── Taze meyve ──
  if (has('elma', 'armut', 'muz', 'portakal', 'mandalina', 'limon', 'greyfurt', 'çilek', 'cilek',
    'kiraz', 'vişne', 'visne', 'şeftali', 'seftali', 'kayısı taze', 'taze kayısı', 'erik', 'üzüm taze',
    'taze üzüm', 'incir taze', 'taze incir', 'nar', 'karpuz', 'kavun', 'ayva', 'kivi', 'avokado',
    'mürdüm', 'murdum', 'meyve sepeti', 'meyve paketi')) return 'taze-meyve';

  return null;
}

function decodeJsonLdProducts(html) {
  const blocks = [...html.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)];
  for (const b of blocks) {
    try {
      const j = JSON.parse(b[1]);
      if (j['@type'] === 'Product' && j.offers && parseFloat(j.offers.price) > 1) return j;
    } catch {}
  }
  return null;
}

function cleanDescription(desc) {
  if (!desc) return '';
  const paras = desc.split(/\r?\n\r?\n+/).map((p) => p.trim()).filter(Boolean);
  if (paras.length <= 1) return `<p>${desc.trim()}</p>`;
  return paras.map((p) => `<p>${p.replace(/\r?\n/g, '<br>')}</p>`).join('\n');
}

const NISA_NAME_RE = /nisa\s*organik/i;

// ── 1) Mevcut (Organik Express'teki TÜM) ürün adlarını normalize edilmiş set olarak çıkar ──
const existingRaw = readFileSync(join(ROOT, 'storage/app/import/existing-active.txt'), 'utf8');
const existingNamesRaw = existingRaw.split('\n').map((l) => (l.split('|')[0] || '').trim()).filter(Boolean);
const existingNames = new Set(existingNamesRaw.map(norm));
console.error(`Mevcut aktif ürün adı: ${existingNames.size}`);

// ── 1b) Zaten DOĞRUDAN kaynağından ithal ettiğimiz markalar: Nisa bu markaları da
//     satıyor olabilir (ör. Beyorganik, Essen) — aynı ürünü ikinci kez, farklı
//     kelime sırasıyla eklememek için bu markalardaki Nisa ürünlerini TAMAMEN atla.
const knownProducersRaw = readFileSync(join(ROOT, 'storage/app/import/known-producers.txt'), 'utf8');
const knownProducers = new Set(knownProducersRaw.split('\n').map((l) => normBrand(l.split('|')[0] || '')).filter(Boolean));
console.error(`Zaten ithal edilen marka: ${knownProducers.size}`);

// ── 2) Mevcut nisaorganik.json varsa oku (yoksa boş başlat) ──
let catalog = { source: 'nisaorganik', scraped_at: new Date().toISOString(), products: [] };
if (existsSync(CATALOG_FILE)) {
  catalog = JSON.parse(readFileSync(CATALOG_FILE, 'utf8'));
}
const seenSlugs = new Set(catalog.products.map((p) => p.slug));
const seenNames = new Set(catalog.products.map((p) => norm(p.name)));

// ── 3) Sitemap'ten ürün URL'lerini al ──
const sm = await get('https://nisaorganik.com.tr/sitemap.xml');
let urls = [...new Set([...sm.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)].map((m) => m[1]))]
  .filter((u) => u.endsWith('.html'));
console.error(`${urls.length} ürün sayfası bulundu`);
if (LIMIT) urls = urls.slice(0, LIMIT);

// ── 4) Sayfalardan JSON-LD çek ──
const rows = await pool(urls, async (u) => {
  const html = await get(u);
  if (!html) return null;
  const ld = decodeJsonLdProducts(html);
  if (!ld || !ld.name) return null;
  const name = ld.name.replace(/\s*[-–]\s*%100.*$/i, '').replace(/\s{2,}/g, ' ').trim();
  const price = parseFloat(ld.offers?.price || '0') || 0;
  const sku = ld.sku || null;
  const images = Array.isArray(ld.image) ? ld.image.map((i) => (typeof i === 'string' ? i : i.url)).filter(Boolean) : [];
  const brandRaw = (ld.brand?.name || '').trim();
  const brand = brandRaw && !NISA_NAME_RE.test(brandRaw) ? brandRaw : null;
  return { url: u, name, price, sku, images, description: ld.description || '', brand };
}, 10);

const items = rows.filter(Boolean);
console.error(`${items.length} üründe veri bulundu`);

// ── 5) Dedup: mevcut katalogda aynı isim VEYA zaten ithal ettiğimiz bir markaysa
//     VEYA bu dosyada/bu ÇALIŞTIRMADA zaten varsa atla. ÖNEMLİ: Nisa'nın kendi
//     sitesinde AYNI ürün birden çok URL'de (farklı SKU/kayıt) tekrarlanabiliyor
//     — bu yüzden "bu çalıştırmada görüldü" kontrolü tek geçişte (aşağıdaki filter
//     predicate'inin KENDİSİ İÇİNDE, ayrı bir enrichment loop'unda DEĞİL) yapılmalı;
//     aksi halde aynı partideki yinelemeler birbirini yakalayamaz.
const skipped = [];
const batchSeenNames = new Set();
const fresh = items.filter((it) => {
  const n = norm(it.name);
  if (existingNames.has(n)) { skipped.push(it.name + ' [mevcut katalogda var]'); return false; }
  if (seenNames.has(n) || batchSeenNames.has(n)) { skipped.push(it.name + ' [bu kaynakta zaten var]'); return false; }
  if (it.price <= 0) { skipped.push(it.name + ' [fiyat yok]'); return false; }
  if (it.brand && knownProducers.has(normBrand(it.brand))) { skipped.push(`[${it.brand}] ${it.name} [marka zaten ithal edildi]`); return false; }
  batchSeenNames.add(n);
  return true;
});
console.error(`${fresh.length} YENİ ürün, ${skipped.length} atlandı`);

// ── 6) Kategori + görsel + JSON satırı (DRY modda görsel indirilmez, dosyaya yazılmaz) ──
let ok = 0, fail = 0;
const noCat = [];
const newProducts = [];

for (const it of fresh) {
  const cat = mapCategory(it.name, it.brand);
  if (!cat) { noCat.push(`[${it.brand || '-'}] ${it.name}`); continue; }
  let slug = slugify(it.name);
  if (!slug) continue;
  let base = slug, n = 2;
  while (seenSlugs.has(slug)) { slug = `${base}-${n++}`; }
  seenSlugs.add(slug);
  seenNames.add(norm(it.name));

  const dest = join(IMG, `${slug}-1.jpg`);
  if (!DRY && it.images[0] && !existsSync(dest)) {
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
    : it.name;

  newProducts.push({
    slug,
    name: it.name,
    category: cat,
    producer: it.brand,
    sku: it.sku,
    price: Math.round(it.price * 100) / 100,
    unit: 'adet',
    unit_amount: 1,
    is_weight_based: false,
    images: (!DRY && existsSync(dest)) ? [`products/${slug}-1.jpg`] : [],
    short_description: shortDesc,
    description: desc,
    meta_title: `${it.name} | Organik Express`,
    meta_description: shortDesc,
  });
}

if (!DRY) {
  catalog.products.push(...newProducts);
  catalog.scraped_at = new Date().toISOString();
  writeFileSync(CATALOG_FILE, JSON.stringify(catalog, null, 2), 'utf8');
}

const cats = {};
newProducts.forEach((p) => { cats[p.category] = (cats[p.category] || 0) + 1; });
console.log(`\nnisaorganik: ${newProducts.length} ${DRY ? '(DRY-RUN, kaydedilmedi)' : 'YENİ ürün eklendi'} | görsel ${ok} ok / ${fail} hata`);
Object.entries(cats).sort((a, b) => b[1] - a[1]).forEach(([k, v]) => console.log(`  ${k.padEnd(24)} ${v}`));
if (noCat.length) {
  console.log(`\nKATEGORİSİZ (${noCat.length}):`);
  noCat.slice(0, 60).forEach((n) => console.log('  ' + n));
}
if (skipped.length) {
  console.log(`\nATLANDI (${skipped.length}):`);
  skipped.slice(0, 30).forEach((n) => console.log('  ' + n));
}
