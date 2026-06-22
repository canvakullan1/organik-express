<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Producer;
use App\Models\Product;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = collect();

        $urls->push(['loc' => route('home'), 'priority' => '1.0', 'freq' => 'daily']);
        $urls->push(['loc' => route('blog.index'), 'priority' => '0.6', 'freq' => 'weekly']);
        $urls->push(['loc' => route('producers.index'), 'priority' => '0.6', 'freq' => 'monthly']);

        Category::query()->where('is_active', true)->get()->each(function ($c) use ($urls) {
            $urls->push(['loc' => route('category.show', $c->slug), 'lastmod' => $c->updated_at, 'priority' => '0.7', 'freq' => 'weekly']);
        });

        Product::active()->get()->each(function ($p) use ($urls) {
            $urls->push(['loc' => route('product.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.8', 'freq' => 'weekly']);
        });

        Producer::active()->get()->each(function ($p) use ($urls) {
            $urls->push(['loc' => route('producer.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.5', 'freq' => 'monthly']);
        });

        Page::published()->get()->each(function ($p) use ($urls) {
            $urls->push(['loc' => route('page.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.4', 'freq' => 'monthly']);
        });

        Post::published()->get()->each(function ($p) use ($urls) {
            $urls->push(['loc' => route('blog.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.5', 'freq' => 'monthly']);
        });

        return Response::view('sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $content = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Disallow: /sepet\n"
            . "Disallow: /odeme\n"
            . "Disallow: /hesabim\n"
            . "Disallow: /giris\n"
            . "Disallow: /uye-ol\n\n"
            . 'Sitemap: ' . url('/sitemap.xml') . "\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
