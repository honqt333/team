<?php

namespace App\Http\Requests\Concerns;

trait TenantAware
{
    protected function tenantId(): int
    {
        return (int) (auth()->user()?->tenant_id ?? 0);
    }

    protected function centerId(): ?int
    {
        return auth()->user()?->current_center_id;
    }

    /**
     * Rule: Field must reference a row scoped to current tenant.
     */
    protected function tenantExistsRule(string $table, string $column = 'id'): string
    {
        return "exists:{$table},{$column},tenant_id,{$this->tenantId()}";
    }

    /**
     * Rule: Field must reference a row in current tenant + center.
     */
    protected function centerExistsRule(string $table, string $column = 'id'): string
    {
        $centerId = $this->centerId() ?? 0;
        return "exists:{$table},{$column},tenant_id,{$this->tenantId()},center_id,{$centerId}";
    }
}
