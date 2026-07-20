<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\FilterableRequest;
use App\Http\Requests\Concerns\PaginatesRequest;
use App\Http\Requests\Concerns\SortableRequest;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    use PaginatesRequest, SortableRequest, FilterableRequest;

    public function authorize(): bool
    {
        return true; // Reports accessible to all authenticated users
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
