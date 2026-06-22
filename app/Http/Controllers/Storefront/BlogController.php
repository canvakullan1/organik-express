<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::published()->with('category')->latest('published_at');

        if ($slug = $request->input('kategori')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        return view('storefront.blog.index', [
            'posts' => $query->paginate(9)->withQueryString(),
            'categories' => BlogCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'activeCategory' => $slug,
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::published()->with(['category', 'author'])->where('slug', $slug)->firstOrFail();

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')->take(3)->get();

        return view('storefront.blog.show', compact('post', 'related'));
    }
}
