<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCertificate extends Model
{
    protected $fillable = ['product_id', 'title', 'type', 'file', 'issued_at'];

    protected $casts = ['issued_at' => 'date'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
