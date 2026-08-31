/**
 * Canlı site denetimi: sitemap + menü linkleri, HTTP durumları, boş kategoriler,
 * kırık görsel, fiyatsız ürün, sayfa içi hata izleri.
 *   node scripts/site_audit.mjs
 */
const BASE = 'https://www.organikexpress.com';
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SiteAudit' };

async function get(u, tries = 2) {
  for (let i = 0; i < tries; i++) {
    try {
      const r = await fetch(u, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
      return { s: r.status, t: await r.text(), url: r.url };
    } catch (e) { if (i === tries - 1) return { s: 0, t: '', err: String(e).slice(0, 50) }; }
  }
}
async function head(u) {
  try {
    const r = await fetch(u, { method: 'GET', headers: { ...UA, Range: 'bytes=0-64' }, redirect: 'follow', signal: AbortSignal.timeout(20000) });
    return r.status;
  } catch { return 0; }
}
async function pool(items, fn, conc = 8) {
  const out = []; let i = 0;
  await Promise.all(Array.from({ length: Math.min(conc, items.length) }, async () => {
    while (i < items.length) { const k = i++; out[k] = await fn(items[k], k); }
  }));
  return out;
}
const locs = (x) => [...x.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)].map(m => m[1].replace(/&amp;/g, '&'));

// 1) sitemap
const sm = await get(BASE + '/sitemap.xml');
let urls = locs(sm.t);
const subs = urls.filter(u => /\.xml/i.test(u));
if (subs.length) { urls = []; for (const s of subs) urls.push(...locs((await get(s)).t)); }
urls = [...new Set(urls)].map(u => u.replace(/^http:/, 'https:'));
console.log(`SITEMAP: ${urls.length} url (${subs.length} alt harita)`);

const cats = urls.filter(u => /\/kategori\//.test(u));
const prods = urls.filter(u => /\/urun\//.test(u));
const pages = urls.filter(u => !/\/kategori\/|\/urun\//.test(u));
console.log(`  kategori:${cats.length} urun:${prods.length} diger:${pages.length}`);

// 2) sabit sayfalar + menü linkleri
const home = await get(BASE + '/');
const menu = [...new Set([...home.t.matchAll(/href="(https:\/\/www\.organikexpress\.com\/[^"#?]*)"/gi)].map(m => m[1]))];
const staticPages = [...new Set([...pages, ...menu.filter(u => !/\/urun\/|\/kategori\//.test(u))])];
console.log(`\n--- SAYFALAR (${staticPages.length}) ---`);
const pr = await pool(staticPages, async (u) => ({ u, r: await get(u) }));
for (const { u, r } of pr) {
  const bad = r.s >= 400 || r.s === 0;
  const err = /Whoops|Server Error|Exception|SQLSTATE/i.test(r.t);
  if (bad || err) console.log(`  ${r.s} ${err ? '[HATA IZI] ' : ''}${u.replace(BASE, '')}`);
}
console.log('  (yukarida listelenmeyen tum sayfalar 200)');

// 3) kategoriler: durum + urun sayisi
console.log(`\n--- KATEGORILER (${cats.length}) ---`);
const cr = await pool(cats, async (u) => {
  const r = await get(u);
  const tot = r.t.match(/Toplam <span[^>]*>(\d+)<\/span>/);
  const cards = (r.t.match(/\/urun\/[a-z0-9-]+/gi) || []);
  const uniq = new Set(cards).size;
  const empty = /Bu kategoride henüz ürün yok/i.test(r.t);
  return { u, s: r.s, total: tot ? +tot[1] : uniq, empty, err: /Whoops|SQLSTATE|Server Error/i.test(r.t) };
});
const emptyCats = [];
for (const c of cr.sort((a, b) => a.total - b.total)) {
  const name = c.u.replace(BASE + '/kategori/', '');
  if (c.s !== 200 || c.err) console.log(`  !! ${c.s} ${name} ${c.err ? '(HATA)' : ''}`);
  else if (c.empty || c.total === 0) { emptyCats.push(name); console.log(`  BOS: ${name}`); }
}
console.log(`  dolu kategori: ${cr.filter(c => !c.empty && c.total > 0).length} | bos: ${emptyCats.length}`);

// 4) urun ornekleme (200 rastgele)
const sample = prods.sort(() => Math.random() - 0.5).slice(0, 200);
console.log(`\n--- URUN ORNEKLEMESI (${sample.length}/${prods.length}) ---`);
const badProd = [], noPrice = [], noImg = [];
const imgSet = new Set();
await pool(sample, async (u) => {
  const r = await get(u);
  if (r.s !== 200) { badProd.push(`${r.s} ${u.replace(BASE, '')}`); return; }
  if (/Whoops|SQLSTATE|Server Error/i.test(r.t)) { badProd.push(`HATA ${u.replace(BASE, '')}`); return; }
  const priceM = r.t.match(/(\d[\d.,]*)\s*(?:TL|₺)/);
  const price = priceM ? parseFloat(priceM[1].replace(/\./g, '').replace(',', '.')) : 0;
  if (!price) noPrice.push(u.replace(BASE, ''));
  const im = [...r.t.matchAll(/src="([^"]*\/storage\/products\/[^"]+)"/g)].map(m => m[1]);
  if (!im.length) noImg.push(u.replace(BASE, ''));
  im.slice(0, 1).forEach(i => imgSet.add(i));
}, 8);
console.log(`  acilmayan: ${badProd.length}`); badProd.slice(0, 10).forEach(x => console.log('    ' + x));
console.log(`  fiyatsiz : ${noPrice.length}`); noPrice.slice(0, 10).forEach(x => console.log('    ' + x));
console.log(`  gorselsiz: ${noImg.length}`); noImg.slice(0, 10).forEach(x => console.log('    ' + x));

// 5) gorsel kontrolu
const imgs = [...imgSet].slice(0, 120);
console.log(`\n--- GORSEL (${imgs.length} ornek) ---`);
const ir = await pool(imgs, async (i) => ({ i, s: await head(i) }), 10);
const brokenImg = ir.filter(x => x.s !== 200 && x.s !== 206);
console.log(`  kirik: ${brokenImg.length}`);
brokenImg.slice(0, 10).forEach(x => console.log(`    ${x.s} ${x.i.replace(BASE, '')}`));
