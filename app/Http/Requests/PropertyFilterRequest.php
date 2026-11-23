<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Facing;
use App\Enums\Furnishing;
use App\Enums\PropertyStatus;
use App\Enums\Purpose;
use App\Services\PropertySearch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Public GET filters. Invalid values are validation errors, never silently trusted. */
class PropertyFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'purpose' => ['nullable', Rule::in(array_column(Purpose::cases(), 'value'))],
            'type' => ['nullable', 'array', 'max:12'], 'type.*' => ['string', 'max:60'],
            'location' => ['nullable', 'string', 'max:255'],
            'price_min' => ['nullable', 'numeric', 'min:0', 'max:100000000000'], 'price_max' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
            'size_min' => ['nullable', 'numeric', 'min:0', 'max:10000000'], 'size_max' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'beds' => ['nullable', 'integer', 'min:0', 'max:20'], 'baths' => ['nullable', 'integer', 'min:0', 'max:20'],
            'status' => ['nullable', Rule::in(array_column(PropertyStatus::cases(), 'value'))],
            'furnishing' => ['nullable', Rule::in(array_column(Furnishing::cases(), 'value'))],
            'facing' => ['nullable', Rule::in(array_column(Facing::cases(), 'value'))],
            'parking' => ['nullable', 'integer', 'min:0', 'max:20'],
            'floor_min' => ['nullable', 'integer', 'min:-5', 'max:200'], 'floor_max' => ['nullable', 'integer', 'min:-5', 'max:200'],
            'developer' => ['nullable', 'string', 'max:255'], 'project' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable', 'array', 'max:20'], 'amenities.*' => ['string', 'max:100'],
            'verified' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(array_keys(PropertySearch::SORTS))],
            'view' => ['nullable', Rule::in(['grid', 'list'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:200'], // depth cap against scraping
        ];
    }

    /** Filters without paging/view, empty values removed. */
    public function filters(): array
    {
        return array_filter($this->validated(), fn ($v, $k) => ! in_array($k, ['page', 'view'], true) && $v !== null && $v !== '' && $v !== [], ARRAY_FILTER_USE_BOTH);
    }
}
