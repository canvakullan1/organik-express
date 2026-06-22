<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null; // sadece created_at

    public const TYPES = [
        'page_view' => 'Sayfa Görüntüleme',
        'product_view' => 'Ürün Görüntüleme',
        'add_to_cart' => 'Sepete Ekleme',
        'remove_from_cart' => 'Sepetten Çıkarma',
        'reached_checkout' => 'Ödemeye Ulaşma',
        'purchase' => 'Satın Alma',
    ];

    public const CHANNELS = [
        'direct' => 'Doğrudan',
        'organic' => 'Organik Arama',
        'paid' => 'Ücretli Reklam',
        'social' => 'Sosyal Medya',
        'referral' => 'Yönlendirme',
        'email' => 'E-posta',
        'other' => 'Diğer',
    ];

    protected $fillable = [
        'session_id', 'user_id', 'type', 'product_id', 'variant_id', 'order_id',
        'quantity', 'value', 'channel', 'source', 'medium', 'campaign', 'term',
        'content', 'referrer', 'landing_page', 'url', 'device', 'created_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'quantity' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
