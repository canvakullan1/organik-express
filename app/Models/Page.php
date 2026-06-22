<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSlug;

    protected $slugSource = 'title';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'is_published',
        'show_in_footer', 'footer_group', 'sort_order',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_footer' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public const FOOTER_GROUPS = [
        'kurumsal' => 'Kurumsal',
        'yardim' => 'Yardım',
        'yasal' => 'Yasal',
    ];
}
