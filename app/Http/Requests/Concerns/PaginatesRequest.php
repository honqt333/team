<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

trait PaginatesRequest
{
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', 15);

        return min(max($perPage, 1), 100);
    }

    public function page(): int
    {
        return max((int) $this->input('page', 1), 1);
    }
}
