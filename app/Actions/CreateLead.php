<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RequestType;
use App\Mail\NewInquiryMail;
use App\Models\Lead;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\Permissions;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Single entry point for every public form (ADR-0007). Normalises contact data, records
 * consent and attribution, and de-duplicates rapid repeat submissions.
 */
class CreateLead
{
    public const CONSENT_VERSION = '2026-11';

    /** @param array<string, mixed> $data validated: name, phone, email?, message?, + targets */
    public function __invoke(RequestType $type, array $data, Request $request, array $meta = []): Lead
    {
        $phone = Phone::normalize((string) $data['phone']) ?? $data['phone'];
        $utm = (array) $request->session()->get('utm', []);

        $recent = Lead::query()->where('phone', $phone)->where('request_type', $type->value)
            ->where('property_id', $data['property_id'] ?? null)->where('project_id', $data['project_id'] ?? null)
            ->where('created_at', '>', now()->subMinutes(10))->first();
        if ($recent && $type !== RequestType::SiteVisit) {
            return $recent;
        }

        $lead = Lead::create([
            'user_id' => $request->user()?->id,
            'name' => trim((string) $data['name']),
            'phone' => $phone,
            'email' => isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) ?: null : null,
            'message' => $data['message'] ?? null,
            'request_type' => $type,
            'property_id' => $data['property_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'developer_id' => $data['developer_id'] ?? null,
            'meta' => $meta ?: null,
            'consent_given_at' => now(), 'last_activity_at' => now(),
            'consent_text_version' => self::CONSENT_VERSION,
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'utm_source' => $utm['utm_source'] ?? null, 'utm_medium' => $utm['utm_medium'] ?? null, 'utm_campaign' => $utm['utm_campaign'] ?? null,
            'utm_term' => $utm['utm_term'] ?? null, 'utm_content' => $utm['utm_content'] ?? null,
            'landing_url' => $utm['landing_url'] ?? null, 'referrer' => $utm['referrer'] ?? null,
            // IP kept only as a keyed hash for abuse analysis (docs/14).
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        $this->alertStaff($lead);

        return $lead;
    }

    /** A mail failure must never lose or fail a lead; it is logged without personal data. */
    private function alertStaff(Lead $lead): void
    {
        $this->notifyManagers($lead);

        $to = app(SettingsService::class)->get('leads.notify_email');
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new NewInquiryMail($lead));
        } catch (Throwable $e) {
            Log::warning('New-inquiry alert failed', ['lead_id' => $lead->id, 'error' => $e::class]);
        }
    }

    /** In-app (bell) alert for the people who assign leads, so a new inquiry is never missed. Contains no contact data. */
    private function notifyManagers(Lead $lead): void
    {
        try {
            $managers = User::query()->where('status', 'active')
                ->where(fn ($q) => $q->permission(Permissions::LEADS_ASSIGN)->orWhereHas('roles', fn ($r) => $r->where('name', 'super-admin')))->get();
            Notification::make()->title('New inquiry')->body($lead->request_type->label())->warning()
                ->actions([Action::make('open')->label('Open')->url('/admin/leads/'.$lead->id.'/edit')])->sendToDatabase($managers);
        } catch (Throwable $e) {
            Log::warning('In-app lead alert failed', ['lead_id' => $lead->id, 'error' => $e::class]);
        }
    }
}
