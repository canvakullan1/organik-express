<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'parent_id', 'location', 'label', 'type', 'reference_id',
        'url', 'target_blank', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'target_blank' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPES = [
        'custom' => 'Özel URL',
        'category' => 'Kategori',
        'page' => 'Sayfa',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Tür ve referansa göre çözülmüş URL. */
    public function getResolvedUrlAttribute(): string
    {
        return match ($this->type) {
            'category' => $this->reference_id && ($c = Category::find($this->reference_id))
                ? route('category.show', $c->slug) : '#',
            'page' => $this->reference_id && ($p = Page::find($this->reference_id))
                ? route('page.show', $p->slug) : '#',
            default => $this->url ?: '#',
        };
    }
}
