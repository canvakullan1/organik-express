/**
 * catalog2/*.json ürünlerinin ilk N görselini indirir, ImageMagick ile 800px'e
 * küçültüp storage/app/public/products/{slug}-{n}.jpg olarak kaydeder.
 * Bu dosyalar repoya commit'lenir; import (--skip-images) onları DB'ye bağlar.
 *
 *   node scripts/fetch_images2.mjs                 # tüm kaynaklar
 *   node scripts/fetch_images2.mjs rayaorganik ...  # seçili kaynaklar
 *
 * Var olan dosyayı atlar (resumable).
 */
import { readFileSync, readdirSync, existsSync, mkdirSync, writeFileSync, unlinkSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const exec = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const DATA = join(ROOT, 'database', 'data', 'catalog2');
const OUT = join(ROOT, 'storage', 'app', 'public', 'products');
mkdirSync(OUT, { recursive: true });

const MAX_IMGS = 2;
const MAX_SIDE = 800;
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' };

const only = process.argv.slice(2);
const files = readdirSync(DATA).filter((f) => f.endsWith('.json'))
  .filter((f) => !only.length || only.includes(f.replace(/\.json$/, '')));

/** indirilecek işleri topla */
const jobs = [];
for (const f of files) {
  const d = JSON.parse(readFileSync(join(DATA, f), 'utf8'));
  for (const p of d.products || []) {
    if (!p.slug || !p.images?.length) continue;
    p.images.slice(0, MAX_IMGS).forEach((url, i) => {
      const dest = join(OUT, `${p.slug}-${i + 1}.jpg`);
      if (!existsSync(dest)) jobs.push({ url, dest });
    });
  }
}
console.log(`${jobs.length} görsel inecek (kaynak: ${files.length} dosya)`);

let ok = 0, fail = 0, done = 0;
const CONC = 8;

async function work() {
  while (jobs.length) {
    const job = jobs.pop();
    if (!job) break;
    try {
      const r = await fetch(job.url, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
      if (!r.ok) throw new Error('http ' + r.status);
      const buf = Buffer.from(await r.arrayBuffer());
      if (buf.length < 500) throw new Error('küçük dosya');
      const tmp = job.dest + '.tmp';
      writeFileSync(tmp, buf);
      // ImageMagick: en fazla 800px, beyaz zemin (şeffaflık için), JPEG q82
      await exec('magick', [tmp, '-background', 'white', '-flatten', '-resize', `${MAX_SIDE}x${MAX_SIDE}>`, '-quality', '82', job.dest]);
      unlinkSync(tmp);
      if (!existsSync(job.dest) || statSync(job.dest).size < 300) throw new Error('dönüştürme boş');
      ok++;
    } catch {
      fail++;
      try { if (existsSync(job.dest + '.tmp')) unlinkSync(job.dest + '.tmp'); } catch {}
    }
    if (++done % 50 === 0) process.stdout.write(`  ${ok} ok / ${fail} hata\n`);
  }
}
await Promise.all(Array.from({ length: CONC }, work));
console.log(`Bitti: ${ok} indirildi, ${fail} başarısız.`);
