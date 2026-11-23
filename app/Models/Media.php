<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    public const SIZES = [480, 960, 1600];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_private' => 'boolean', 'is_demo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (self $m) => Storage::disk($m->disk)->deleteDirectory($m->path));
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    /** Public URL of a derivative (480|960|1600) or the stored file. Private media has no public URL. */
    public function url(?int $size = null): ?string
    {
        if ($this->is_private) {
            return null;
        }
        if (! $this->isImage()) {
            return Storage::disk($this->disk)->url($this->path.'/'.$this->filename);
        }
        $size = $size && in_array($size, self::SIZES, true) ? $size : 1600;

        return Storage::disk($this->disk)->url("{$this->path}/{$size}.webp");
    }

    public function srcset(): string
    {
        return collect(self::SIZES)->map(fn ($s) => $this->url($s)." {$s}w")->implode(', ');
    }

    public function altText(string $fallback = ''): string
    {
        return $this->alt ?: $fallback;
    }
}
