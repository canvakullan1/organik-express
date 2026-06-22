<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    protected $fillable = ['name', 'tracking_url_template', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Takip numarasından izleme URL'i üret. */
    public function trackingUrl(string $code): ?string
    {
        if (! $this->tracking_url_template) {
            return null;
        }

        return str_replace(['{code}', '{CODE}'], $code, $this->tracking_url_template);
    }
}
