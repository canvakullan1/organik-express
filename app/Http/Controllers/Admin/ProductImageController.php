<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Ürün görselleri için BASİT, klasik yükleme/silme yöneticisi.
 *
 * Filament'in FilePond + Livewire geçici-upload akışı bu sunucuda (LiteSpeed/cPanel)
 * güvenilir çalışmadığı için; burada standart HTML multipart form POST kullanılır.
 * İmzalı URL yok, Livewire yok — sadece $_FILES + Storage::putFile. Her yerde çalışır.
 *
 * NOT: {product}/{image} parametreleri artık ROUTE MODEL BINDING ile DEĞİL, elle
 * (find + kontrol) çözülüyor — implicit binding bulamazsa Laravel'in çıplak 404
 * sayfasını gösterir ("neden" bilgisi olmadan). Burada her durumda kullanıcı
 * "Görselleri Yönet" sayfasına anlamlı bir mesajla geri döner; ayrıca her adım
 * loglanır ki bir daha sorun olursa `storage/logs/laravel.log`'dan tam olarak
 * hangi adımda ne olduğu görülebilsin.
 */
class ProductImageController extends Controller
{
    /** Sadece panele erişebilen (aktif + rollü) kullanıcılar. */
    private function guard(): void
    {
        $u = auth()->user();
        abort_unless($u && $u->is_active && $u->role !== null, 403);
    }

    public function index(int $product)
    {
        $this->guard();

        $p = Product::withoutGlobalScopes()->find($product);
        if (! $p) {
            abort(404, 'Ürün bulunamadı.');
        }
        $p->load(['images' => fn ($q) => $q->orderBy('sort_order')]);

        return view('admin.product-images', ['product' => $p]);
    }

    public function store(Request $request, int $product)
    {
        $this->guard();

        $p = Product::withoutGlobalScopes()->find($product);
        if (! $p) {
            abort(404, 'Ürün bulunamadı.');
        }

        $request->validate([
            'images'   => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:30720'], // 30MB
        ], [
            'images.required' => 'Lütfen en az bir görsel seçin.',
            'images.*.image'  => 'Yüklenen dosya bir görsel olmalı.',
            'images.*.max'    => 'Görsel en fazla 30 MB olabilir.',
        ]);

        $max = (int) $p->images()->max('sort_order');
        $count = 0;

        foreach ($request->file('images', []) as $file) {
            // 'public' diski → sunucuda public_html/storage/products (PUBLIC_DISK_ROOT),
            // doğrudan /storage/products/xxx olarak servis edilir.
            $path = $file->store('products', 'public');

            $p->images()->create([
                'path'       => $path,
                'sort_order' => ++$max,
            ]);
            $count++;
        }

        Log::error('product-image.store', ['product_id' => $p->id, 'count' => $count, 'user_id' => auth()->id()]);

        return redirect()
            ->route('admin.product-images.index', $p)
            ->with('ok', "{$count} görsel yüklendi.");
    }

    public function destroy(int $product, int $image)
    {
        $this->guard();

        $p = Product::withoutGlobalScopes()->find($product);
        if (! $p) {
            Log::error('product-image.destroy: ürün bulunamadı', ['product' => $product, 'image' => $image]);

            // Filament panel route adı sabit degil (surum/panel id'ye bagli) - riske
            // girmeden panel ana sayfasina donuyoruz.
            return redirect('/admin')->with('img_error', 'Ürün bulunamadı (silinmiş olabilir).');
        }

        $img = ProductImage::find($image);
        if (! $img) {
            // Görsel zaten yok — muhtemelen daha önce silinmiş (çift tıklama, eski sayfa).
            // Kullanıcıyı hâlâ mevcut sayfaya, anlaşılır bir mesajla geri gönder.
            Log::error('product-image.destroy: görsel zaten yok', ['product_id' => $p->id, 'image' => $image]);

            return redirect()->route('admin.product-images.index', $p)
                ->with('ok', 'Bu görsel zaten silinmişti.');
        }

        if ($img->product_id !== $p->id) {
            Log::error('product-image.destroy: görsel başka ürüne ait', [
                'product_id' => $p->id, 'image_id' => $img->id, 'image_owner' => $img->product_id,
            ]);

            return redirect()->route('admin.product-images.index', $p)
                ->with('ok', 'Bu görsel bu ürüne ait değil, listeyi yeniledik.');
        }

        $diskDeleted = Storage::disk('public')->delete($img->path);
        // Deploy'un `cp -R` MERGE kaynağı: Storage::disk('public') PUBLIC_DISK_ROOT'a
        // (prod'da public_html/storage) işaret eder; storage_path('app/public') repo
        // kopyasıdır. İkisi de silinmezse bir sonraki deploy dosyayı "diriltir".
        @unlink(storage_path('app/public/' . $img->path));
        $rowDeleted = $img->delete();

        Log::error('product-image.destroy: silindi', [
            'product_id' => $p->id, 'image_id' => $image, 'path' => $img->path,
            'disk_deleted' => $diskDeleted, 'row_deleted' => $rowDeleted, 'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.product-images.index', $p)
            ->with('ok', 'Görsel silindi.');
    }
}
