<?php

namespace App\Console\Commands;

use App\Models\Producer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Üreticileri database/data/producers.json'dan içe aktarır (slug bazlı upsert).
 * Kapak görselini üreticinin tanıtım videosunun YouTube küçük resminden indirir.
 *
 *   php artisan producers:seed [--reimages]
 */
class SeedProducers extends Command
{
    protected $signature = 'producers:seed {--reimages : Mevcut görselleri de yeniden indir} {--prune : JSON dışındaki (demo) üreticileri sil}';

    protected $description = 'Üreticileri (Üreticilerimiz sayfası) JSON verisinden oluşturur/günceller';

    public function handle(): int
    {
        $path = database_path('data/producers.json');
        if (! is_file($path)) {
            $this->error("Veri dosyası yok: {$path}");

            return self::FAILURE;
        }

        $items = json_decode((string) file_get_contents($path), true);
        if (! is_array($items)) {
            $this->error('producers.json çözümlenemedi (geçersiz JSON).');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $images = 0;

        foreach ($items as $it) {
            $slug = $it['slug'];
            $exists = Producer::where('slug', $slug)->exists();

            $producer = Producer::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $it['name'],
                    'location' => $it['location'] ?? null,
                    'short_description' => $it['short_description'] ?? null,
                    'story' => $it['story'] ?? null,
                    'videos' => $it['videos'] ?? null,
                    'sort_order' => $it['sort_order'] ?? 0,
                    'meta_title' => $it['meta_title'] ?? null,
                    'meta_description' => $it['meta_description'] ?? null,
                    'is_active' => true,
                ],
            );

            $exists ? $updated++ : $created++;

            // Kapak görseli: tanıtım videosunun YouTube küçük resmi (maxres -> hq fallback)
            $needImage = $this->option('reimages') || blank($producer->image);
            $ytId = $it['image_video'] ?? ($it['videos'][0]['id'] ?? null);
            if ($needImage && $ytId) {
                $rel = 'producers/' . $slug . '.jpg';
                if ($this->downloadThumb($ytId, $rel)) {
                    $producer->image = $rel;
                    $producer->save();
                    $images++;
                    $this->line("  görsel indirildi: {$slug}");
                } else {
                    $this->warn("  görsel indirilemedi: {$slug} ({$ytId})");
                }
            }
        }

        $this->info("Üretici -> yeni: {$created}, güncellenen: {$updated}, görsel: {$images}");

        // --prune: JSON'da olmayan (kurulumdan kalan demo) üreticileri kalıcı sil
        if ($this->option('prune')) {
            $keep = array_column($items, 'slug');
            $stale = Producer::whereNotIn('slug', $keep)->get();
            $pruned = 0;
            foreach ($stale as $sp) {
                if (! empty($sp->image)) {
                    Storage::disk('public')->delete($sp->image);
                    $repoFile = storage_path('app/public/' . $sp->image);
                    if (is_file($repoFile)) {
                        @unlink($repoFile);
                    }
                }
                $sp->delete(); // products.producer_id FK -> nullOnDelete (güvenli)
                $this->line("  silindi (demo): {$sp->slug}");
                $pruned++;
            }
            $this->info("Silinen demo üretici: {$pruned}");
        }

        $this->info('Toplam üretici (DB): ' . Producer::count());

        return self::SUCCESS;
    }

    /** YouTube küçük resmini indirir (önce maxres, olmazsa hq) ve iki diske de yazar. */
    private function downloadThumb(string $ytId, string $rel): bool
    {
        foreach (["maxresdefault", "hqdefault"] as $variant) {
            $url = "https://i.ytimg.com/vi/{$ytId}/{$variant}.jpg";
            $data = @file_get_contents($url);
            // hqdefault daima vardır; maxres yoksa YouTube ~1KB gri yer tutucu döndürür
            if ($data !== false && strlen($data) > 5000) {
                // repo storage (deploy kaynağı) — kalıcılık için
                $repoPath = storage_path('app/public/' . $rel);
                @mkdir(dirname($repoPath), 0775, true);
                @file_put_contents($repoPath, $data);
                // servis edilen public disk
                Storage::disk('public')->put($rel, $data);

                return true;
            }
        }

        return false;
    }
}
