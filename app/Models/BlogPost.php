<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['blog_category_id', 'title', 'slug', 'excerpt', 'body', 'seo_title', 'seo_description'])]
class BlogPost extends Model
{
    use HasFactory, HasMedia, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return ['publish_status' => PublishStatus::class, 'published_at' => 'datetime'];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    protected static function booted(): void
    {
        static::creating(fn (self $p) => $p->author_id ??= auth()->id());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published and not scheduled for the future. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('publish_status', PublishStatus::Published->value)->where('published_at', '<=', now());
    }

    /** Markdown rendered with raw HTML stripped and unsafe links disallowed (XSS-safe). */
    public function bodyHtml(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 200));
    }
}
