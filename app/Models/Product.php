<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'producer_id',
        'name', 'slug', 'sku', 'short_description', 'description',
        'storage_info', 'ingredients', 'tax_rate', 'status',
        'is_featured', 'is_seasonal', 'is_new', 'sort_order',
        'estimated_delivery', 'certificate_no', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
        'tax_rate' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_seasonal' => 'boolean',
        'is_new' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(ProductCertificate::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function defaultVariant(): HasMany
    {
        return $this->variants()->where('is_default', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    /** Kapak görseli URL'i (yoksa null → bileşen yer tutucu gösterir). */
    public function getCoverUrlAttribute(): ?string
    {
        $path = $this->images->first()?->path;

        return $path ? asset('storage/' . $path) : null;
    }

    /** Listelemede gösterilecek en düşük varyant fiyatı. */
    public function getPriceFromAttribute(): ?float
    {
        $min = $this->variants->min('price');

        return $min !== null ? (float) $min : null;
    }

    /** Sepete eklemede kullanılacak varsayılan varyant. */
    public function getMainVariantAttribute(): ?ProductVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }

    /** Listelemede gösterilecek indirim yüzdesi (varsa). */
    public function getDiscountPercentAttribute(): ?int
    {
        return $this->main_variant?->discount_percent;
    }
}
