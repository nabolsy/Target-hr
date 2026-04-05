<?php

namespace App\Http\Requests;

use App\Enums\AssetCategory;
use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('asset')?->id ?? $this->route('asset');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'asset_code' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('assets', 'asset_code')->ignore($assetId)],
            'category' => ['sometimes', 'required', Rule::enum(AssetCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'condition' => ['sometimes', 'required', Rule::enum(AssetCondition::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
