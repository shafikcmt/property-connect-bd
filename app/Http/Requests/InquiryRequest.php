<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Developer;
use App\Models\Project;
use App\Models\Property;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Shared contact fields + anti-spam for every public form. Targets (property/project/
 * developer) must be publicly visible; the honeypot and a minimum fill time reject bots.
 */
class InquiryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:30', fn ($a, $v, $fail) => Phone::isValid((string) $v) ?: $fail(__('Please enter a valid phone number, e.g. 01XXXXXXXXX.'))],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'property_id' => ['nullable', 'integer'], 'project_id' => ['nullable', 'integer'], 'developer_id' => ['nullable', 'integer'],
            '_t' => ['nullable'],
        ];
    }

    /** Honeypot: bots fill the hidden "website" field. Controllers treat this as a silent success. */
    public function isBot(): bool
    {
        return $this->filled('website');
    }

    public function messages(): array
    {
        return ['consent.accepted' => __('Please confirm you agree to be contacted about your request.')];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            // Time trap: forms are never legitimately submitted within 3 seconds of rendering.
            $t = (int) $this->input('_t');
            if ($t && now()->timestamp - $t < 3) {
                $v->errors()->add('message', __('Please take a moment to review your request and try again.'));
            }
            foreach ([['property_id', Property::class, 'visible'], ['project_id', Project::class, 'published'], ['developer_id', Developer::class, 'published']] as [$field, $model, $scope]) {
                if ($this->filled($field) && ! $model::query()->$scope()->whereKey($this->input($field))->exists()) {
                    $v->errors()->add($field, __('This listing is no longer available.'));
                }
            }
        }];
    }
}
