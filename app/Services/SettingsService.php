<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Business-editable settings (contact numbers, social links, analytics IDs).
 * Keys are "group.key", e.g. "contact.whatsapp". Reads are cached.
 */
class SettingsService
{
    private const CACHE_KEY = 'pcbd.settings';

    /** Keys the admin settings page manages: key => [default, is_public]. */
    public const DEFINITIONS = [
        'general.site_name' => ['Property Connect BD', true],
        'general.tagline' => ['Connecting Properties, People & Opportunities', true],
        'contact.phone' => [null, true],
        'contact.whatsapp' => [null, true],
        'contact.email' => [null, true],
        'contact.address' => [null, true],
        'social.facebook' => [null, true],
        'social.instagram' => [null, true],
        'social.youtube' => [null, true],
        'social.linkedin' => [null, true],
        'analytics.ga4_id' => [null, true],
        'analytics.meta_pixel_id' => [null, true],
        'seo.default_description' => [null, true],
        'leads.sla_minutes' => [30, false],
        'leads.notify_email' => [null, false],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? (self::DEFINITIONS[$key][0] ?? null);
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $name] = $this->split($key);

        Setting::updateOrCreate(
            ['group' => $group, 'key' => $name],
            ['value' => $value, 'is_public' => self::DEFINITIONS[$key][1] ?? false],
        );

        $this->flush();
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, fn () => Setting::query()->get()
            ->mapWithKeys(fn (Setting $s) => [$s->group.'.'.$s->key => $s->value])
            ->all());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{0: string, 1: string} */
    private function split(string $key): array
    {
        [$group, $name] = explode('.', $key, 2) + [1 => null];
        abort_if($name === null, 500, "Invalid setting key [{$key}].");

        return [$group, $name];
    }
}
