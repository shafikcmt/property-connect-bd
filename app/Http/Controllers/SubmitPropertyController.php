<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Furnishing;
use App\Enums\PublishStatus;
use App\Enums\Purpose;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyType;
use App\Services\ImageProcessor;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Owner submissions (ADR-0009): saved as pending_review, never public until an admin approves. */
class SubmitPropertyController extends Controller
{
    public function create(): View
    {
        return view('forms.submit-property', [
            'types' => PropertyType::active()->get(),
            'locations' => Location::active()->whereIn('type', ['city', 'area', 'sector'])->with('parent')->orderBy('path')->get(),
        ]);
    }

    public function store(Request $request, ImageProcessor $images): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:8', 'max:150'],
            'purpose' => ['required', Rule::in(array_column(Purpose::cases(), 'value'))],
            'property_type_id' => ['required', Rule::exists('property_types', 'id')->where('is_active', true)],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'address_line' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000000000'],
            'size_sqft' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'], 'bathrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'furnishing' => ['nullable', Rule::in(array_column(Furnishing::cases(), 'value'))],
            'description' => ['required', 'string', 'min:40', 'max:5000'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_phone' => ['required', 'string', fn ($a, $v, $f) => Phone::isValid((string) $v) ?: $f(__('Please enter a valid phone number.'))],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=400,min_height=300'],
            'consent' => ['accepted'],
        ], ['consent.accepted' => 'Please confirm you are authorised to list this property and agree to be contacted.']);

        $property = new Property(collect($data)->except(['images', 'consent'])->all());
        $property->owner_phone = Phone::normalize($data['owner_phone']);
        $property->publish_status = PublishStatus::PendingReview;
        $property->submitted_by = $request->user()->id;
        $property->save();

        foreach ($request->file('images') as $file) {
            try {
                $images->store($file, $property, 'gallery', alt: $property->title);
            } catch (\InvalidArgumentException) {
                // skip unreadable files; the admin reviewer sees the image count
            }
        }

        return redirect()->route('account.submissions')->with('status', 'Thank you! Your property was submitted for review. It will appear on the site once approved.');
    }
}
