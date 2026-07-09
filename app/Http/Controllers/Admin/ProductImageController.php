<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Ürün görselleri için BASİT, klasik yükleme/silme yöneticisi.
 *
 * Filament'in FilePond + Livewire geçici-upload akışı bu sunucuda (LiteSpeed/cPanel)
 * güvenilir çalışmadığı için; burada standart HTML multipart form POST kullanılır.
 * İmzalı URL yok, Livewire yok — sadece $_FILES + Storage::putFile. Her yerde çalışır.
 */
class ProductImageController extends Controller
{
    /** Sadece panele erişebilen (aktif + rollü) kullanıcılar. */
    private function guard(): void
    {
        $u = auth()->user();
        abort_unless($u && $u->is_active && $u->role !== null, 403);
    }

    public function index(Product $product)
    {
        $this->guard();
        $product->load(['images' => fn ($q) => $q->orderBy('sort_order')]);

        return view('admin.product-images', ['product' => $product]);
    }

    public function store(Request $request, Product $product)
    {
        $this->guard();

        $request->validate([
            'images'   => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:30720'], // 30MB
        ], [
            'images.required' => 'Lütfen en az bir görsel seçin.',
            'images.*.image'  => 'Yüklenen dosya bir görsel olmalı.',
            'images.*.max'    => 'Görsel en fazla 30 MB olabilir.',
        ]);

        $max = (int) $product->images()->max('sort_order');
        $count = 0;

        foreach ($request->file('images', []) as $file) {
            // 'public' diski → sunucuda public_html/storage/products (PUBLIC_DISK_ROOT),
            // doğrudan /storage/products/xxx olarak servis edilir.
            $path = $file->store('products', 'public');

            $product->images()->create([
                'path'       => $path,
                'sort_order' => ++$max,
            ]);
            $count++;
        }

        return redirect()
            ->route('admin.product-images.index', $product)
            ->with('ok', "{$count} görsel yüklendi.");
    }

    public function destroy(Product $product, ProductImage $image)
    {
        $this->guard();
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()
            ->route('admin.product-images.index', $product)
            ->with('ok', 'Görsel silindi.');
    }
}
