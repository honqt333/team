<?php

declare(strict_types=1);

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

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::withCount('services')
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->input('status') === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $allowedSorts = ['name_ar', 'name_en', 'services_count', 'is_active', 'sort_order', 'created_at'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'sort_order';
        $direction = strtolower((string) ($request->input('direction') ?? $request->input('order', ''))) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $direction);

        $departments = $query->paginate(25)->withQueryString();

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'filters' => array_merge($request->only(['search', 'status']), [
                'sort' => $sort,
                'direction' => $direction,
            ]),
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
            'name_en' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Department::create($validated);

        return back()->with('success', __('messages.department_added'));
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
            'name_en' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return back()->with('success', __('messages.department_updated'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        // Prevent deletion if department has services
        if ($department->services()->exists()) {
            return back()->withErrors([
                'delete' => __('departments.cannot_delete_has_services'),
            ]);
        }

        $department->delete();

        return back()->with('success', __('messages.department_removed'));
    }

    public function toggleActive(Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $department->update(['is_active' => ! $department->is_active]);

        return back()->with('success', __('messages.department_updated'));
    }
}
