<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Model "saving" anında slug boşsa name alanından otomatik üretir.
 * Kullanan modelde $slugSource tanımlanabilir (varsayılan: name).
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = property_exists($model, 'slugSource') ? $model->slugSource : 'name';

            if (empty($model->slug) && ! empty($model->{$source})) {
                $model->slug = static::uniqueSlug(Str::slug($model->{$source}), $model);
            }
        });
    }

    protected static function uniqueSlug(string $base, $model): string
    {
        $slug = $base ?: 'kayit';
        $i = 1;

        while (
            static::where('slug', $slug)
                ->when($model->getKey(), fn ($q) => $q->where($model->getKeyName(), '!=', $model->getKey()))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
