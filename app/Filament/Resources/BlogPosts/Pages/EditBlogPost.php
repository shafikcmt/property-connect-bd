<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Enums\PublishStatus;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill(collect($data)->except(['publish_status', 'published_at'])->all());
        $record->publish_status = PublishStatus::tryFrom($data['publish_status'] ?? 'draft') ?? PublishStatus::Draft;
        $record->published_at = $data['published_at'] ?? ($record->publish_status === PublishStatus::Published ? ($record->published_at ?? now()) : null);
        $record->save();

        return $record;
    }
}
