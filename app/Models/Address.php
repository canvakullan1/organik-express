<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'title', 'type', 'is_corporate',
        'first_name', 'last_name', 'company_name', 'tax_office', 'tax_number',
        'phone', 'city', 'district', 'neighborhood', 'address', 'postal_code', 'is_default',
    ];

    protected $casts = [
        'is_corporate' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->is_corporate
            ? (string) $this->company_name
            : trim($this->first_name . ' ' . $this->last_name);
    }

    /** Sipariş anlık görüntüsü için diziye çevir. */
    public function toSnapshot(): array
    {
        return [
            'title' => $this->title,
            'name' => $this->full_name,
            'is_corporate' => $this->is_corporate,
            'company_name' => $this->company_name,
            'tax_office' => $this->tax_office,
            'tax_number' => $this->tax_number,
            'phone' => $this->phone,
            'city' => $this->city,
            'district' => $this->district,
            'neighborhood' => $this->neighborhood,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
        ];
    }
}
