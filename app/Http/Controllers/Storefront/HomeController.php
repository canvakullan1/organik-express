<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\Post;
use App\Models\Producer;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $with = ['images', 'variants', 'category', 'tags'];

        // Tarifler bölümü: "Tarifler" blog kategorisi varsa ondan, yoksa son yazılardan
        $recipeCat = BlogCategory::where('slug', 'tarifler')->first();
        $recipes = Post::published()->with('category')
            ->when($recipeCat, fn ($q) => $q->where('blog_category_id', $recipeCat->id))
            ->latest('published_at')->take(3)->get();

        return view('storefront.home', [
            'heroBanners' => Banner::active()->position('hero')->orderBy('sort_order')->get(),
            'featured' => Product::active()->with($with)->where('is_featured', true)->latest()->take(8)->get(),
            'seasonal' => Product::active()->with($with)->where('is_seasonal', true)->latest()->take(8)->get(),
            'newest' => Product::active()->with($with)->where('is_new', true)->latest()->take(8)->get(),
            'bestsellers' => Product::active()->with($with)->orderBy('sort_order')->take(8)->get(),
            'shortcutCategories' => Category::active()->roots()->orderBy('sort_order')->take(8)->get(),
            'producers' => Producer::active()->orderBy('sort_order')->take(4)->get(),
            'recipes' => $recipes,
        ]);
    }
}
