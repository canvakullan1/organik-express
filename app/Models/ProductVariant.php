<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'sku', 'unit', 'unit_amount',
        'price', 'compare_at_price', 'stock', 'track_stock',
        'is_weight_based', 'is_default', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'unit' => ProductUnit::class,
        'unit_amount' => 'decimal:3',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock' => 'decimal:3',
        'track_stock' => 'boolean',
        'is_weight_based' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** İndirim yüzdesi (compare_at_price üzerinden). */
    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return (int) round((1 - ($this->price / $this->compare_at_price)) * 100);
    }

    public function getInStockAttribute(): bool
    {
        return ! $this->track_stock || $this->stock > 0;
    }
}
