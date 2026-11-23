<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSlugs;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    use ResolvesSlugs;

    public function index(?string $category = null): View
    {
        $cat = $category ? BlogCategory::where('slug', $category)->firstOrFail() : null;
        $posts = BlogPost::live()->with(['category', 'coverMedia', 'author'])->when($cat, fn ($q) => $q->where('blog_category_id', $cat->id))->latest('published_at')->paginate(9);

        return view('blog.index', ['posts' => $posts, 'category' => $cat, 'categories' => BlogCategory::whereHas('posts', fn ($q) => $q->live())->orderBy('name')->get()]);
    }

    public function show(string $slug)
    {
        $found = $this->resolveSlug(BlogPost::query()->live(), $slug, 'blog.show');
        if (! $found instanceof BlogPost) {
            return $found;
        }
        $post = $found->load(['category', 'coverMedia', 'author']);
        $related = BlogPost::live()->where('id', '!=', $post->id)->with(['category', 'coverMedia'])->latest('published_at')->limit(3)->get();

        return view('blog.show', compact('post', 'related'));
    }
}
