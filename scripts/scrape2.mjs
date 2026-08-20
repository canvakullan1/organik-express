/**
 * 3. dalga katalog kazıyıcı (Node 18+, bağımlılık yok).
 * Çıktı: database/data/catalog2/<kaynak>.json  (import:catalog2 formatı)
 *
 *   node scripts/scrape2.mjs                # hepsi
 *   node scripts/scrape2.mjs wefood-baharat # tek kaynak
 *
 * Kaynaklar:
 *   wefood-baharat : Shopify koleksiyonu   -> baharat-aktar (İNDİRİMSİZ = compare_at)
 *   ogstore-baharat: Wix sitemap + filtre  -> baharat-aktar
 *   elta-ada       : WooCommerce sayfaları -> süt/et/zeytinyağı (FİYAT YOK -> draft)
 *   rayaorganik    : Ticimax sitemap       -> isimden kategoriye dağıtım
 *
 * Kaynak metin KOPYALANMAZ; özgün TR açıklama + meta üretilir.
 */
import { writeFileSync, mkdirSync, readFileSync, readdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const OUT = join(ROOT, 'database', 'data', 'catalog2');
mkdirSync(OUT, { recursive: true });

const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' };

async function get(url, tries = 2) {
  for (let i = 0; i < tries; i++) {
    try {
      const r = await fetch(url, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
      if (r.ok) return await r.text();
    } catch {}
  }
  return '';
}

/** paralel getir (eşzamanlılık sınırlı) */
async function getAll(urls, conc = 10, label = '') {
  const out = new Map();
  let i = 0, done = 0;
  await Promise.all(Array.from({ length: Math.min(conc, urls.length) }, async () => {
    while (i < urls.length) {
      const u = urls[i++];
      out.set(u, await get(u));
      if (++done % 25 === 0) process.stderr.write(`  ${label} ${done}/${urls.length}\r`);
    }
  }));
  process.stderr.write(`  ${label} ${done}/${urls.length}\n`);
  return out;
}

const TR = { ç: 'c', Ç: 'c', ğ: 'g', Ğ: 'g', ı: 'i', İ: 'i', ö: 'o', Ö: 'o', ş: 's', Ş: 's', ü: 'u', Ü: 'u', â: 'a', î: 'i', û: 'u' };
const slugify = (s) => String(s).replace(/[çÇğĞıİöÖşŞüÜâîû]/g, (c) => TR[c] || c)
  .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

const locs = (xml) => [...xml.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)]
  .map((m) => m[1].replace(/&amp;/g, '&').trim());

const decode = (s) => String(s || '')
  .replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#0?39;/g, "'")
  .replace(/&#8217;/g, '’').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&nbsp;/g, ' ').trim();

/** sayfadaki JSON-LD düğümleri */
function ldNodes(html) {
  const out = [];
  for (const m of html.matchAll(/<script[^>]*application\/ld\+json[^>]*>([\s\S]*?)<\/script>/gi)) {
    try {
      const j = JSON.parse(m[1].trim());
      out.push(...(j['@graph'] || [j]));
    } catch {}
  }
  return out;
}

function ldProduct(html) {
  for (const n of ldNodes(html)) {
    const t = Array.isArray(n['@type']) ? n['@type'].join('|') : n['@type'];
    if (t && /Product/i.test(t)) return n;
  }
  return null;
}

const num = (v) => {
  if (v == null) return 0;
  if (typeof v === 'number') return v;
  let s = String(v).replace(/[^0-9,.]/g, '');
  if (s.includes(',') && s.includes('.')) s = s.replace(/\./g, '').replace(',', '.');
  else if (s.includes(',')) s = s.replace(',', '.');
  return parseFloat(s) || 0;
};

/** ---- prod kategori eşlemesi (canlıdaki gerçek slug'lar) ---- */
function mapCategory(text) {
  const t = String(text).toLowerCase();
  const has = (...k) => k.some((x) => t.includes(x));
  // kelime sınırlı eşleşme: "et" → "S(et)", "Pak(et)" gibi yanlış eşleşmeleri önler
  const word = (...k) => k.some((x) =>
    new RegExp(`(^|[^a-z0-9çğıöşü])${x}([^a-z0-9çğıöşü]|$)`, 'i').test(t));

  if (has('bebek', 'ek gıda', 'ek gida')) return 'bebek';
  if (has('glutensiz')) return 'glutensiz';
  // "Köfte Baharatı", "Pizza Baharatı" → et değil, baharat
  if (has('baharat')) return 'baharat-aktar';
  if (has('yumurta')) return 'yumurta';
  if (has('zeytinyağ', 'zeytinyag', 'sızma', 'sizma', 'ladoia', 'ladolia', 'zeytin')) return 'zeytin-zeytinyagi-yag';
  if (has('süt', 'yoğurt', 'yogurt', 'peynir', 'kefir', 'tereyağ', 'tereyag', 'kaymak', 'ayran', 'çökelek') || word('sut', 'lor')) return 'sut-urunleri';
  // 'köfte' kelime sınırlı: "Köftelik Bulgur" ete düşmesin
  if (has('sucuk', 'kıyma', 'kiyma', 'kuşbaşı', 'kusbasi', 'bonfile', 'pastırma', 'pastirma', 'salam', 'tavuk', 'pirzola', 'kavurma') || word('et', 'dana', 'kuzu', 'köfte', 'kofte')) return 'et-sarkuteri';
  if (has('reçel', 'recel', 'marmelat', 'pekmez', 'tahin', 'kahvalt', 'keçiboynuzu', 'keciboynuzu') || word('bal', 'balı', 'bali')) return 'kahvaltilik-recel';
  if (has('sirke', 'sırke', 'salça', 'salca', 'ketçap', 'ketchup', 'sosu', 'püre', 'pure', 'doğranmış', 'dogranmis', 'rendelenmiş', 'rendelenmis', 'ekşi', 'eksi', 'konserve', 'turşu', 'tursu') || word('sos')) return 'sos-salca-sirke';
  if (has('baharat', 'kekik', 'nane', 'kimyon', 'zerdeçal', 'zerdecal', 'tarçın', 'tarcin', 'karabiber', 'pul biber', 'kırmızı biber', 'zencefil', 'sumak', 'çörek otu', 'corek otu', 'susam', 'anason', 'rezene', 'defne', 'aktar', 'kişniş', 'kisnis', 'yenibahar', 'safran', 'çemen', 'toz biber', 'biber') || word('köri', 'kori')) return 'baharat-aktar';
  if (has('mercimek', 'nohut', 'fasulye', 'bulgur', 'pirinç', 'pirinc', 'makarna', 'spagetti', 'erişte', 'eriste', 'bakliyat', 'bezelye', 'barbunya', 'buğday', 'bugday', 'kinoa', 'şehriye', 'sehriye', 'yulaf', 'irmik', 'nişasta', 'nisasta', 'tarhana') || word('un', 'unu')) return 'bakliyat-makarna';
  // net tatlı ürünleri kuruyemişten önce yakala ("Antep Fıstıklı Helva" → tatlı)
  if (has('helva', 'çikolata', 'cikolata', 'lokum', 'gofret', 'bisküvi', 'biskuvi', 'kurabiye')) return 'tatli-cikolata';
  if (has('ceviz', 'badem', 'fındık', 'findik', 'fıstık', 'fistik', 'üzüm', 'uzum', 'kayısı', 'kayisi', 'incir', 'hurma', 'kuru ', 'leblebi', 'çekirdek', 'kuruyemiş', 'tohum') || word('dut')) return 'kuruyemis-kurutulmus';
  if (has('kahve', 'içecek', 'icecek', 'limonata', 'kombucha', 'çayı', 'cayi', 'nar suyu', 'meyve suyu', 'elma suyu', 'portakal suyu') || word('çay', 'cay', 'shot')) return 'icecek-cay';
  if (has('çikolata', 'cikolata', 'bisküvi', 'biskuvi', 'kurabiye', 'tatlı', 'lokum', 'helva', 'gofret', 'cin mısır', 'cin misir', 'patlak', 'patlamış', 'cips', 'kraker') || word('bar')) return 'tatli-cikolata';
  if (has('sabun', 'şampuan', 'sampuan', 'deterjan', 'temizl', 'diş macunu', 'krem')) return 'dogal-yasam-temizlik';
  if (has('ekmek', 'lavaş', 'lavas')) return 'firin-ekmek';
  return null;
}

const shortDesc = (n) => `${n}, özenle seçilmiş organik içeriğiyle sofranıza doğallık katar; katkısız ve güvenilir.`;
const longDesc = (n, brand) =>
  `<p><strong>${n}</strong>, ${brand} güvencesiyle Organik Express rafında. Doğal ve katkısız içeriğiyle sağlıklı beslenmeye katkı sağlar.</p>` +
  `<ul><li>Organik içerik, katkısız üretim</li><li>Özenli paketleme ile taze teslim</li><li>Güvenle sipariş verin, kapınıza gelsin</li></ul>`;

function mkProduct({ name, slug, category, price, images, brand, sku = null, status, meta }) {
  const sd = shortDesc(name);
  return {
    slug: slug || slugify(name),
    name,
    category,
    sku,
    price: Math.round((price + Number.EPSILON) * 100) / 100,
    unit: 'adet',
    unit_amount: 1,
    is_weight_based: false,
    images: (images || []).filter(Boolean).slice(0, 3),
    short_description: sd,
    description: longDesc(name, brand),
    meta_title: meta || `${name} | Organik Express`,
    meta_description: sd,
    ...(status ? { status } : {}),
  };
}

const save = (source, products) => {
  writeFileSync(join(OUT, `${source}.json`),
    JSON.stringify({ source, scraped_at: new Date().toISOString(), products }, null, 2), 'utf8');
  console.log(`${source}: ${products.length} ürün -> database/data/catalog2/${source}.json`);
};

/* =========================== 1) WEFOOD BAHARAT =========================== */
async function wefoodBaharat() {
  const products = [];
  const seen = new Set();
  for (let page = 1; page <= 5; page++) {
    const txt = await get(`https://wefood.com.tr/collections/organik-baharat/products.json?limit=250&page=${page}`);
    let items = [];
    try { items = JSON.parse(txt).products || []; } catch {}
    if (!items.length) break;
    for (const it of items) {
      const handle = it.handle;
      if (!handle || seen.has(handle)) continue;
      seen.add(handle);
      const v = it.variants?.[0] || {};
      const price = num(v.price);
      const cmp = num(v.compare_at_price);
      const listPrice = cmp > price ? cmp : price; // İNDİRİMSİZ (liste) fiyat
      if (listPrice <= 0) continue;
      products.push(mkProduct({
        name: decode(it.title),
        slug: handle,
        category: 'baharat-aktar',
        price: listPrice,
        images: (it.images || []).map((i) => i.src),
        brand: 'Wefood',
        sku: v.sku || null,
        meta: `${decode(it.title)} | Organik Baharat - Organik Express`,
      }));
    }
  }
  save('wefood-baharat', products);
}

/* =========================== 2) OGSTORE BAHARAT ========================== */
const OG_INCLUDE = /kekik|nane|kimyon|zerdecal|tarcin|karabiber|biber|kori|zencefil|sumak|corek-otu|susam|anason|rezene|defne|kisnis|yenibahar|safran|cemen|baharat|harci|karvi|tuz|nohut-unu|maydanoz|fesleg|reyhan|adacayi|papatya|ihlamur|kus-uzumu/i;
const OG_EXCLUDE = /shot|icecek|limonata|bonbon|cay-|-cayi|kombucha|meyveli|vitamin|paket-|avantajli|set-|hediye|bebek|biskuvi|puree|kraker/i;

async function ogstoreBaharat() {
  const sm = await get('https://www.ogstore.com.tr/sitemap.xml');
  let urls = locs(sm);
  const subs = urls.filter((u) => /\.xml/i.test(u));
  if (subs.length) {
    urls = [];
    for (const s of subs) urls.push(...locs(await get(s)));
  }
  urls = [...new Set(urls)].filter((u) => /ogstore\.com\.tr\/[a-z0-9-]{5,}$/i.test(u));
  const cand = urls.filter((u) => OG_INCLUDE.test(u) && !OG_EXCLUDE.test(u));
  console.error(`  ogstore aday: ${cand.length}/${urls.length}`);

  const pages = await getAll(cand, 8, 'ogstore');
  const products = [];
  const seen = new Set();
  for (const [, html] of pages) {
    if (!html || html.length < 500) continue;
    const p = ldProduct(html);
    if (!p?.name) continue;
    const name = decode(p.name);
    const price = num(p.offers?.price ?? p.offers?.[0]?.price);
    if (price <= 0) continue;
    // isimden de doğrula: baharat/aktariye dışını alma
    if (mapCategory(name) !== 'baharat-aktar') continue;
    const slug = slugify(name);
    if (seen.has(slug)) continue;
    seen.add(slug);
    const img = Array.isArray(p.image) ? p.image : [p.image];
    products.push(mkProduct({
      name, slug, category: 'baharat-aktar', price,
      images: img.map((i) => (typeof i === 'string' ? i : i?.url)),
      brand: 'OG Store', sku: p.sku || null,
      meta: `${name} | Organik Baharat - Organik Express`,
    }));
  }
  save('ogstore-baharat', products);
}

/* ============================= 3) ELTA-ADA ============================== */
/** Fiyatlar müşteri tarafından girilecek → price 0 + status draft (pasif). */
async function eltaAda() {
  const idx = await get('https://elta-ada.com.tr/urunler/');
  const urls = [...new Set([...idx.matchAll(/href="(https:\/\/elta-ada\.com\.tr\/organik-urunler\/[^"#]+)"/gi)].map((m) => m[1]))];
  console.error(`  elta-ada ürün: ${urls.length}`);
  const pages = await getAll(urls, 6, 'elta-ada');

  const products = [];
  const seen = new Set();
  for (const [url, html] of pages) {
    if (!html || html.length < 500) continue;
    const og = html.match(/<meta[^>]+property=["']og:title["'][^>]+content=["']([^"']+)/i);
    let name = decode(og?.[1] || '');
    name = name.replace(/\s*[-|]\s*Elta[- ]?Ada.*$/i, '').trim();
    if (!name) continue;
    const ogi = html.match(/<meta[^>]+property=["']og:image["'][^>]+content=["']([^"']+)/i);
    const imgs = [ogi?.[1]].filter(Boolean);
    const slugFromUrl = url.replace(/\/$/, '').split('/').pop();
    const slug = slugify(name).startsWith('organik') ? slugify(name) : `organik-${slugify(name)}`;
    if (seen.has(slug)) continue;
    seen.add(slug);
    const cat = mapCategory(name + ' ' + slugFromUrl) || 'sut-urunleri';
    products.push(mkProduct({
      name, slug, category: cat,
      price: 0,                 // fiyatı müşteri girecek
      images: imgs, brand: 'Elta-Ada',
      status: 'draft',          // fiyat girilene kadar yayında değil
      meta: `${name} | Organik Çiftlik Ürünü - Organik Express`,
    }));
  }
  save('elta-ada', products);
}

/* ============================ 4) RAYAORGANIK ============================ */
async function rayaOrganik() {
  const sm = await get('https://www.rayaorganik.com/sitemap/products/0.xml');
  let urls = [...new Set(locs(sm))].map((u) => u.replace(/^http:\/\//, 'https://'));
  console.error(`  raya ürün url: ${urls.length}`);
  const pages = await getAll(urls, 8, 'raya');

  const products = [];
  const seen = new Set();
  let noCat = 0;
  for (const [, html] of pages) {
    if (!html || html.length < 500) continue;
    const p = ldProduct(html);
    if (!p?.name) continue;
    const name = decode(p.name).replace(/\s*[-|]\s*Raya\s*Organik\s*$/i, '').trim();
    let price = num(p.offers?.price ?? p.offers?.[0]?.price);
    if (price <= 0) {
      const m = html.match(/"productPriceStr"\s*:\s*"([^"]+)"/);
      price = num(m?.[1]);
    }
    if (price <= 0) continue;
    const cat = mapCategory(name);
    if (!cat) { noCat++; if (noCat <= 25) console.error(`    [kategorisiz] ${name}`); continue; }
    const slug = slugify(name);
    if (!slug || seen.has(slug)) continue;
    seen.add(slug);
    const img = Array.isArray(p.image) ? p.image : [p.image];
    products.push(mkProduct({
      name, slug, category: cat, price,
      images: img.map((i) => (typeof i === 'string' ? i : i?.url)),
      brand: 'Raya Organik', sku: p.sku || null,
    }));
  }
  console.error(`  raya kategorisiz atlandı: ${noCat}`);
  save('rayaorganik', products);
}

/* ==================== YENİDEN SINIFLANDIRMA (--recat) ==================== */
/** Var olan JSON'ları yeniden indirmeden mapCategory ile tazeler. */
function recat(sources) {
  for (const f of readdirSync(OUT).filter((x) => x.endsWith('.json'))) {
    const src = f.replace(/\.json$/, '');
    if (sources.length && !sources.includes(src)) continue;
    const d = JSON.parse(readFileSync(join(OUT, f), 'utf8'));
    let changed = 0;
    for (const p of d.products || []) {
      // sabit kategorili kaynakları (baharat/elta-ada) bozma
      if (/baharat|elta-ada|meyve-sebze|organikgiller-ms/.test(src)) continue;
      const c = mapCategory(p.name);
      if (c && c !== p.category) { p.category = c; changed++; }
    }
    if (changed) {
      writeFileSync(join(OUT, f), JSON.stringify(d, null, 2), 'utf8');
      console.log(`${src}: ${changed} ürünün kategorisi güncellendi`);
    }
  }
}
/* ================================= ANA ================================= */
const jobs = { 'wefood-baharat': wefoodBaharat, 'ogstore-baharat': ogstoreBaharat, 'elta-ada': eltaAda, rayaorganik: rayaOrganik };
const only = process.argv[2];
if (only === '--recat') {
  recat(process.argv.slice(3));
  process.exit(0);
}
for (const [k, fn] of Object.entries(jobs)) {
  if (only && k !== only) continue;
  console.error(`### ${k}`);
  await fn();
}
