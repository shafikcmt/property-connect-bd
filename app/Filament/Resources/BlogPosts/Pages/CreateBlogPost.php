<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Enums\PublishStatus;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $post = new BlogPost(collect($data)->except(['publish_status', 'published_at'])->all());
        $post->publish_status = PublishStatus::tryFrom($data['publish_status'] ?? 'draft') ?? PublishStatus::Draft;
        $post->published_at = $data['published_at'] ?? ($post->publish_status === PublishStatus::Published ? now() : null);
        $post->save();

        return $post;
    }
}
