<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use App\Services\AuditLogger;
use App\Services\ListingPublisher;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/** Header actions shared by the property, project and developer edit pages. */
class ListingActions
{
    /** @return list<Action> */
    public static function all(): array
    {
        return [self::publish(), self::unpublish(), self::verify(), self::revoke(), self::feature()];
    }

    public static function publish(): Action
    {
        return Action::make('publish')->label('Publish')->icon('heroicon-o-globe-alt')->color('success')
            ->visible(fn (Model $record) => $record->publish_status !== PublishStatus::Published && auth()->user()?->can('publish', $record))
            ->requiresConfirmation()
            ->modalDescription(function (Model $record) {
                $problems = app(ListingPublisher::class)->problems($record);

                return $problems ? 'Cannot publish yet: '.implode(' ', $problems) : 'This makes the listing visible on the public website.';
            })
            ->action(function (Model $record, $livewire) {
                abort_unless(auth()->user()?->can('publish', $record), 403);
                try {
                    app(ListingPublisher::class)->publish($record);
                } catch (\RuntimeException $e) {
                    Notification::make()->title('Not ready to publish')->body($e->getMessage())->danger()->send();

                    return;
                }
                Notification::make()->title('Published')->success()->send();
                $livewire->refreshFormData(['publish_status']);
            });
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish')->label('Unpublish')->icon('heroicon-o-eye-slash')->color('gray')
            ->visible(fn (Model $record) => $record->publish_status === PublishStatus::Published && auth()->user()?->can('publish', $record))
            ->requiresConfirmation()
            ->action(function (Model $record) {
                abort_unless(auth()->user()?->can('publish', $record), 403);
                app(ListingPublisher::class)->unpublish($record);
                Notification::make()->title('Moved back to draft')->success()->send();
            });
    }

    public static function verify(): Action
    {
        return Action::make('verify')->label('Mark verified')->icon('heroicon-o-check-badge')->color('info')
            ->visible(fn (Model $record) => ! $record->is_verified && auth()->user()?->can('verify', $record))
            ->schema([
                TextInput::make('method')->label('How was this verified?')->required()->maxLength(100)->placeholder('e.g. Site visit, document check'),
                Textarea::make('notes')->label('Notes (internal)')->rows(3),
            ])
            ->action(function (array $data, Model $record) {
                abort_unless(auth()->user()?->can('verify', $record), 403);
                $record->markVerified(auth()->user(), $data['method'], $data['notes'] ?? null);
                app(AuditLogger::class)->log('listing.verified', $record, null, ['method' => $data['method']]);
                Notification::make()->title('Verification recorded')->success()->send();
            });
    }

    public static function revoke(): Action
    {
        return Action::make('revoke')->label('Revoke verification')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (Model $record) => $record->is_verified && auth()->user()?->can('verify', $record))
            ->schema([TextInput::make('reason')->required()->maxLength(255)])
            ->action(function (array $data, Model $record) {
                abort_unless(auth()->user()?->can('verify', $record), 403);
                $record->revokeVerification($data['reason']);
                app(AuditLogger::class)->log('listing.verification_revoked', $record, null, ['reason' => $data['reason']]);
                Notification::make()->title('Verification revoked')->success()->send();
            });
    }

    public static function feature(): Action
    {
        return Action::make('feature')->label(fn (Model $r) => $r->isFeaturedNow() ? 'Edit / end featuring' : 'Feature')->icon('heroicon-o-star')->color('warning')
            ->visible(fn (Model $record) => auth()->user()?->can('publish', $record))
            ->fillForm(fn (Model $r) => ['is_featured' => $r->is_featured, 'featured_until' => $r->featured_until])
            ->schema([
                Toggle::make('is_featured')->label('Featured'),
                DateTimePicker::make('featured_until')->label('Featured until (optional)')->helperText('Leave empty for no expiry. Featured items are labelled "Featured" publicly.'),
            ])
            ->action(function (array $data, Model $record) {
                abort_unless(auth()->user()?->can('publish', $record), 403);
                $record->forceFill(['is_featured' => (bool) $data['is_featured'], 'featured_until' => $data['is_featured'] ? ($data['featured_until'] ?? null) : null])->save();
                app(AuditLogger::class)->log('listing.featured', $record, null, ['featured' => $record->is_featured, 'until' => $record->featured_until?->toDateTimeString()]);
                Notification::make()->title('Featuring updated')->success()->send();
            });
    }
}
