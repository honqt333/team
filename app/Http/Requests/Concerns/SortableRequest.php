<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

trait SortableRequest
{
    public function sortBy(): string
    {
        return $this->input('sort_by', 'id');
    }

    public function sortDir(): string
    {
        $dir = strtolower($this->input('sort_dir', 'desc'));

        return in_array($dir, ['asc', 'desc']) ? $dir : 'desc';
    }
}
