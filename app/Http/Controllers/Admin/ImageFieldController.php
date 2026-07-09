<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Post;
use App\Models\Producer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Tekli görsel alanları için BASİT klasik yükleyici (kategori, banner, üretici, marka,
 * kutu, blog kapağı). Filament FilePond bu sunucuda güvenilir çalışmadığı için, düz
 * HTML multipart form POST ile alanı doldurur/temizler. Whitelist (map) dışına çıkılamaz.
 */
class ImageFieldController extends Controller
{
    /** key => [model, alan, klasör, etiket, filament-edit-route] */
    private function map(): array
    {
        return [
            'category'      => [Category::class, 'image',        'categories', 'Kategori Görseli',           'filament.admin.resources.categories.edit'],
            'producer'      => [Producer::class, 'image',        'producers',  'Üretici Görseli',            'filament.admin.resources.producers.edit'],
            'brand'         => [Brand::class,    'logo',         'brands',     'Marka Logosu',               'filament.admin.resources.brands.edit'],
            'bundle'        => [Bundle::class,   'image',        'bundles',    'Kutu Görseli',               'filament.admin.resources.bundles.edit'],
            'post'          => [Post::class,     'cover_image',  'blog',       'Kapak Görseli',              'filament.admin.resources.posts.edit'],
            'banner'        => [Banner::class,   'image',        'banners',    'Banner Görseli (masaüstü)',  'filament.admin.resources.banners.edit'],
            'banner-mobile' => [Banner::class,   'mobile_image', 'banners',    'Banner Görseli (mobil)',     'filament.admin.resources.banners.edit'],
        ];
    }

    /** @return array{record: \Illuminate\Database\Eloquent\Model, field: string, dir: string, label: string, editRoute: string, key: string} */
    private function resolve(string $key, int|string $id): array
    {
        $u = auth()->user();
        abort_unless($u && $u->is_active && $u->role !== null, 403);

        $map = $this->map();
        abort_unless(isset($map[$key]), 404);

        [$model, $field, $dir, $label, $editRoute] = $map[$key];

        return [
            'record'    => $model::findOrFail($id),
            'field'     => $field,
            'dir'       => $dir,
            'label'     => $label,
            'editRoute' => $editRoute,
            'key'       => $key,
        ];
    }

    public function show(string $key, int|string $id)
    {
        return view('admin.image-field', $this->resolve($key, $id) + ['id' => $id]);
    }

    public function store(Request $request, string $key, int|string $id)
    {
        $c = $this->resolve($key, $id);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:30720'],
        ], [
            'image.required' => 'Lütfen bir görsel seçin.',
            'image.image'    => 'Dosya bir görsel olmalı.',
            'image.max'      => 'Görsel en fazla 30 MB olabilir.',
        ]);

        $record = $c['record'];
        $field = $c['field'];

        if ($record->{$field}) {
            Storage::disk('public')->delete($record->{$field});
        }
        $record->{$field} = $request->file('image')->store($c['dir'], 'public');
        $record->save();

        return redirect()->route('admin.image-field.show', ['key' => $key, 'id' => $id])
            ->with('ok', 'Görsel kaydedildi.');
    }

    public function destroy(string $key, int|string $id)
    {
        $c = $this->resolve($key, $id);
        $record = $c['record'];
        $field = $c['field'];

        if ($record->{$field}) {
            Storage::disk('public')->delete($record->{$field});
        }
        $record->{$field} = null;
        $record->save();

        return redirect()->route('admin.image-field.show', ['key' => $key, 'id' => $id])
            ->with('ok', 'Görsel kaldırıldı.');
    }
}
