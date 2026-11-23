<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateLead;
use App\Enums\RequestType;
use App\Http\Requests\InquiryRequest;
use App\Models\NewsletterSubscriber;
use App\Models\Property;
use App\Models\PropertyReport;
use App\Models\PropertyType;
use App\Models\SiteVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Every public form that ends up as a lead (ADR-0007). */
class FormController extends Controller
{
    private const THANKS = 'Thank you — we have received your request and will contact you soon.';

    public function inquiry(InquiryRequest $request, CreateLead $create): RedirectResponse
    {
        if ($request->isBot()) {
            return back()->with('status', self::THANKS);
        }
        $data = $request->validated();
        $type = match (true) {
            ! empty($data['property_id']) => RequestType::PropertyInquiry,
            ! empty($data['project_id']) => RequestType::ProjectInquiry,
            ! empty($data['developer_id']) => RequestType::DeveloperInquiry,
            default => RequestType::Contact,
        };
        if ($type === RequestType::PropertyInquiry) { // inherit project/developer for reporting
            $p = Property::find($data['property_id']);
            $data['project_id'] ??= $p?->project_id;
            $data['developer_id'] ??= $p?->developer_id;
        }
        $create($type, $data, $request);

        return back()->with('status', self::THANKS)->with('form', 'inquiry');
    }

    public function siteVisit(InquiryRequest $request, CreateLead $create): RedirectResponse
    {
        if ($request->isBot()) {
            return back()->with('status', self::THANKS);
        }
        $extra = $request->validate([
            'preferred_date' => ['required', 'date', 'after:today', 'before:'.now()->addDays(61)->toDateString()],
            'preferred_window' => ['required', Rule::in(['morning', 'afternoon', 'evening'])],
        ]);
        abort_unless($request->filled('property_id') || $request->filled('project_id'), 422);

        $data = $request->validated();
        $lead = $create(RequestType::SiteVisit, $data, $request, ['preferred_date' => $extra['preferred_date'], 'window' => $extra['preferred_window']]);
        SiteVisit::create([
            'lead_id' => $lead->id, 'user_id' => $request->user()?->id, 'property_id' => $data['property_id'] ?? null, 'project_id' => $data['project_id'] ?? null,
            'preferred_date' => $extra['preferred_date'], 'preferred_window' => $extra['preferred_window'],
        ]);

        return back()->with('status', 'Your site visit request has been received. We will confirm the time with you shortly.')->with('form', 'visit');
    }

    public function form(string $kind): View
    {
        abort_unless(in_array($kind, ['sell', 'rent-out', 'valuation', 'assistance'], true), 404);

        return view('forms.'.$kind, ['types' => PropertyType::active()->get()]);
    }

    public function submitRequest(InquiryRequest $request, CreateLead $create, string $kind): RedirectResponse
    {
        if ($request->isBot()) {
            return back()->with('status', self::THANKS);
        }
        $map = ['sell' => RequestType::Sell, 'rent-out' => RequestType::Rent, 'valuation' => RequestType::Valuation, 'assistance' => RequestType::Assistance];
        abort_unless(isset($map[$kind]), 404);

        $extra = $request->validate([
            'property_type' => ['nullable', 'string', 'max:60'], 'area' => ['nullable', 'string', 'max:150'],
            'size_sqft' => ['nullable', 'numeric', 'min:1', 'max:10000000'], 'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'expected_price' => ['nullable', 'numeric', 'min:0', 'max:100000000000'], 'address' => ['nullable', 'string', 'max:255'],
        ]);
        $create($map[$kind], $request->validated(), $request, array_filter($extra, fn ($v) => $v !== null && $v !== ''));

        return back()->with('status', self::THANKS);
    }

    public function report(Request $request, string $slug): RedirectResponse
    {
        $property = Property::visible()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'reason' => ['required', Rule::in(['sold_or_unavailable', 'wrong_price', 'wrong_details', 'fake_or_scam', 'other'])],
            'details' => ['nullable', 'string', 'max:1000'], 'contact' => ['nullable', 'string', 'max:255'],
        ]);
        if ($request->filled('website')) {
            return back()->with('status', 'Thank you — our team will review this listing.');
        }
        PropertyReport::create($data + ['property_id' => $property->id, 'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'))]);

        return back()->with('status', 'Thank you — our team will review this listing.')->with('form', 'report');
    }

    public function newsletter(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255'], 'consent' => ['accepted']], ['consent.accepted' => 'Please tick the box to subscribe.']);
        if (! $request->filled('website')) {
            NewsletterSubscriber::firstOrCreate(['email' => mb_strtolower($data['email'])], ['consent_at' => now(), 'token' => Str::random(40)]);
        }

        return back()->with('status', 'You are subscribed. Thank you!')->with('form', 'newsletter');
    }
}
