<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'subject', 'heading', 'body_html', 'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /** Otomatik gönderilen sistem şablonları (admin için referans). */
    public const KEYS = [
        'order_placed' => 'Sipariş Alındı',
        'order_shipped' => 'Kargoya Verildi',
        'order_delivered' => 'Teslim Edildi',
        'order_cancelled' => 'Sipariş İptal',
        'order_payment_reminder' => 'Havale Ödeme Hatırlatma',
    ];

    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
