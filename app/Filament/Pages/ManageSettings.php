<?php

namespace App\Filament\Pages;

use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::SETTINGS_MANAGE) ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $settings = app(SettingsService::class);
        $this->form->fill(collect(SettingsService::DEFINITIONS)
            ->mapWithKeys(fn ($def, $key) => [self::field($key) => $settings->get($key)])
            ->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('General'))->columns(2)->schema([
                TextInput::make(self::field('general.site_name'))->label(__('Site name'))->required()->maxLength(100),
                TextInput::make(self::field('general.tagline'))->label(__('Tagline'))->maxLength(160),
            ]),
            Section::make(__('Contact (shown publicly)'))->columns(2)->schema([
                TextInput::make(self::field('contact.phone'))->label(__('Phone'))->tel()->maxLength(30),
                TextInput::make(self::field('contact.whatsapp'))->label(__('WhatsApp number'))->tel()->maxLength(30)
                    ->helperText(__('International format, e.g. +8801XXXXXXXXX.')),
                TextInput::make(self::field('contact.email'))->label(__('Email'))->email()->maxLength(255),
                Textarea::make(self::field('contact.address'))->label(__('Office address'))->rows(2)->maxLength(500),
            ]),
            Section::make(__('Social links'))->columns(2)->schema([
                TextInput::make(self::field('social.facebook'))->label('Facebook')->url()->maxLength(255),
                TextInput::make(self::field('social.instagram'))->label('Instagram')->url()->maxLength(255),
                TextInput::make(self::field('social.youtube'))->label('YouTube')->url()->maxLength(255),
                TextInput::make(self::field('social.linkedin'))->label('LinkedIn')->url()->maxLength(255),
            ]),
            Section::make(__('Analytics (loaded only after cookie consent)'))->columns(2)->schema([
                TextInput::make(self::field('analytics.ga4_id'))->label('GA4 measurement ID')->maxLength(30)->regex('/^G-[A-Z0-9]+$/'),
                TextInput::make(self::field('analytics.meta_pixel_id'))->label('Meta Pixel ID')->maxLength(30)->regex('/^[0-9]+$/'),
            ]),
            Section::make(__('SEO & operations'))->columns(2)->schema([
                Textarea::make(self::field('seo.default_description'))->label(__('Default meta description'))->rows(2)->maxLength(160),
                TextInput::make(self::field('leads.sla_minutes'))->label(__('Lead first-response target (minutes)'))->numeric()->minValue(1)->maxValue(1440),
                TextInput::make(self::field('leads.notify_email'))->label(__('Email new inquiries to'))->email()->maxLength(255)
                    ->helperText(__('Staff alert only; leave empty to disable. Requires working mail settings.')),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label(__('Save settings'))->submit('save')])]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $settings = app(SettingsService::class);
        $changed = [];

        foreach (SettingsService::DEFINITIONS as $key => $_) {
            $new = $state[self::field($key)] ?? null;
            $new = is_string($new) ? trim($new) : $new;
            $new = $new === '' ? null : $new;

            $old = $settings->all()[$key] ?? null;

            if ($old !== $new) {
                $settings->set($key, $new);
                $changed[] = $key;
            }
        }

        if ($changed) {
            // Record which keys changed — not their values (may include contact details).
            app(AuditLogger::class)->log('settings.updated', null, null, ['keys' => $changed]);
        }

        Notification::make()->title(__('Settings saved'))->success()->send();
    }

    private static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }
}
