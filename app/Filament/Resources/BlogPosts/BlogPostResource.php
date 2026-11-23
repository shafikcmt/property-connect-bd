<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Select::make('blog_category_id')->label('Category')->options(fn () => BlogCategory::orderBy('name')->pluck('name', 'id'))
                ->createOptionForm([TextInput::make('name')->required()->maxLength(100)])->createOptionUsing(fn (array $d) => BlogCategory::create($d)->id),
            Select::make('publish_status')->label('Status')->options(['draft' => 'Draft', 'published' => 'Published'])->required()->default('draft'),
            DateTimePicker::make('published_at')->label('Publish at')->helperText('Leave empty to publish immediately when status is Published. Future dates schedule the post.'),
            TextInput::make('excerpt')->maxLength(400)->columnSpanFull(),
            TextInput::make('title_bn')->label('Title (বাংলা, optional)')->maxLength(255)->columnSpanFull(),
            TextInput::make('excerpt_bn')->label('Excerpt (বাংলা, optional)')->maxLength(400)->columnSpanFull(),
            Textarea::make('body_bn')->label('Body (বাংলা, optional)')->rows(12)->columnSpanFull()->helperText('Shown to Bangla readers instead of the English body. Markdown supported.'),
            Textarea::make('body')->required()->rows(18)->columnSpanFull()->helperText('Markdown supported. Raw HTML is stripped for safety.'),
            TextInput::make('seo_title')->maxLength(255),
            Textarea::make('seo_description')->rows(2)->maxLength(320),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->with('category'))->columns([
            TextColumn::make('title')->searchable()->limit(50)->weight('medium'), TextColumn::make('category.name'),
            TextColumn::make('publish_status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
            TextColumn::make('published_at')->dateTime()->sortable(),
        ])->defaultSort('id', 'desc')->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBlogPosts::route('/'), 'create' => CreateBlogPost::route('/create'), 'edit' => EditBlogPost::route('/{record}/edit')];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
