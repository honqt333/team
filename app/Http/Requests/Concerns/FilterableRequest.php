<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait FilterableRequest
{
    public function applySearch(Builder $query, array $fields): Builder
    {
        if ($search = $this->input('search')) {
            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        return $query;
    }

    public function applyDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        if ($from = $this->input('date_from')) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to = $this->input('date_to')) {
            $query->whereDate($column, '<=', $to);
        }
        return $query;
    }
}
