<?php

namespace App\Filament\Support;

use App\Models\Developer;
use App\Services\AuditLogger;
use App\Services\ImageProcessor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/** Image galleries, floor plans, logo, brochure and private documents for any listing model. */
class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Images & documents';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alt')->label('Alt text (describe the image)')->maxLength(255),
            TextInput::make('caption')->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('thumb')->label('')->state(fn ($record) => $record->isImage() ? $record->url(480) : null)->height(56),
                TextColumn::make('collection')->badge(),
                TextColumn::make('alt')->limit(40)->placeholder('— add alt text —'),
                IconColumn::make('is_private')->boolean()->label('Private'),
                IconColumn::make('is_demo')->boolean()->label('Demo')->visible(fn () => ! app()->isProduction()),
            ])
            ->headerActions([
                Action::make('upload')->label('Upload')->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn () => auth()->user()?->can('update', $this->getOwnerRecord()))
                    ->schema([
                        Select::make('collection')->options([
                            'gallery' => 'Gallery image', 'floor_plans' => 'Floor plan', 'logo' => 'Logo', 'brochure' => 'Brochure (PDF, public)',
                            'documents' => 'Private document (never public)',
                        ])->default(fn () => $this->getOwnerRecord() instanceof Developer ? 'logo' : 'gallery')->required()
                            ->helperText(fn () => $this->getOwnerRecord() instanceof Developer ? 'Upload the company logo as "Logo" — it is required to publish.' : null),
                        FileUpload::make('files')->multiple()->required()->disk('local')->directory('tmp-uploads')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])->maxSize(15360)->maxFiles(30),
                        TextInput::make('alt')->label('Alt text (applies to all uploaded images)')->maxLength(255),
                    ])
                    ->action(function (array $data) {
                        abort_unless(auth()->user()?->can('update', $this->getOwnerRecord()), 403);
                        $processor = app(ImageProcessor::class);
                        $private = $data['collection'] === 'documents';
                        $ok = 0;
                        foreach ($data['files'] as $file) {
                            try {
                                $processor->store(Storage::disk('local')->path($file), $this->getOwnerRecord(), $data['collection'], $private, $data['alt'] ?? null);
                                $ok++;
                            } catch (\Throwable $e) {
                                Notification::make()->title('Upload failed')->body($e->getMessage())->danger()->send();
                            } finally {
                                Storage::disk('local')->delete($file);
                            }
                        }
                        if ($ok) {
                            app(AuditLogger::class)->log('media.uploaded', $this->getOwnerRecord(), null, ['count' => $ok, 'collection' => $data['collection']]);
                            Notification::make()->title("{$ok} file(s) uploaded")->success()->send();
                        }
                    }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public function canCreate(): bool
    {
        return false; // uploads go through the processor, never raw inserts
    }
}
