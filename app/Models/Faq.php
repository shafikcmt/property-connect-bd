<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBangla;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'question', 'answer', 'question_bn', 'answer_bn', 'sort_order', 'is_published'])]
class Faq extends Model
{
    use HasBangla;

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->orderBy('category')->orderBy('sort_order');
    }
}
