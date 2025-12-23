<?php

namespace App\Http\Controllers\App;

use App\Models\Department;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::withCount('services')
            ->ordered()
            ->paginate(20);

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $centerId = auth()->user()->current_center_id;

        $validated = $request->validate([
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')
                    ->where(fn ($query) => $query->where('center_id', $centerId))
                    ->withoutTrashed(),
            ],
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Department::create($validated);

        return back();
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name_ar' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('departments')
                    ->where(fn ($query) => $query->where('center_id', $department->center_id))
                    ->ignore($department->id)
                    ->withoutTrashed(),
            ],
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return back();
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        // Prevent deletion if department has services
        if ($department->services()->exists()) {
            return back()->withErrors([
                'delete' => __('departments.cannot_delete_has_services')
            ]);
        }

        $department->delete();

        return back();
    }

    public function toggleActive(Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $department->update(['is_active' => !$department->is_active]);

        return back();
    }
}
