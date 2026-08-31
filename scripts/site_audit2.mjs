const BASE = 'https://www.organikexpress.com';
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' };
const get = async (u) => { try { const r = await fetch(u, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) }); return { s: r.status, t: await r.text() }; } catch (e) { return { s: 0, t: '', e: String(e).slice(0,40) }; } };

// 1) kritik akis sayfalari
console.log('--- KRITIK SAYFALAR ---');
for (const p of ['/sepet','/odeme','/giris','/kayit','/hesabim','/arama?q=organik','/blog','/iletisim','/sertifikalar','/ureticiler','/kategori/sut-urunleri']) {
  const r = await get(BASE + p);
  const err = /Whoops|SQLSTATE|Server Error|Exception/i.test(r.t);
  const empty = /henüz ürün yok|sonuç bulunamadı|bulunamadı/i.test(r.t);
  console.log(`  ${String(r.s).padEnd(4)} ${p.padEnd(28)} ${err ? 'HATA-IZI ' : ''}${empty ? '(bos icerik)' : ''}`);
}

// 2) arama testleri
console.log('\n--- ARAMA ---');
for (const q of ['kekik','bal','sirke','mercimek','peynir','zeytinyagi','karmalife','raya']) {
  const r = await get(`${BASE}/arama?q=${encodeURIComponent(q)}`);
  const m = r.t.match(/için\s*(\d+)\s*sonuç/i) || r.t.match(/(\d+)\s*sonuç/i);
  console.log(`  ${q.padEnd(12)} ${m ? m[1] + ' sonuç' : 'SONUC YOK'}`);
}

// 3) menude gorunen kategoriler
console.log('\n--- MENU KATEGORILERI ---');
const home = await get(BASE + '/');
const menuCats = [...new Set([...home.t.matchAll(/\/kategori\/([a-z0-9-]+)/g)].map(m => m[1]))];
for (const c of menuCats) {
  const r = await get(`${BASE}/kategori/${c}`);
  const tot = r.t.match(/Toplam <span[^>]*>(\d+)<\/span>/);
  const cards = new Set((r.t.match(/\/urun\/[a-z0-9-]+/gi) || [])).size;
  const n = tot ? +tot[1] : cards;
  console.log(`  ${c.padEnd(26)} ${String(n).padStart(4)} ürün ${n === 0 ? '  <<< BOS (menude gorunuyor)' : ''}`);
}
