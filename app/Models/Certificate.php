<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'site_certificates';

    protected $fillable = [
        'name', 'label', 'group', 'description', 'image', 'file',
        'valid_until', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
