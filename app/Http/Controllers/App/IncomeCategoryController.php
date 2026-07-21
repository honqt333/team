<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\IncomeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class IncomeCategoryController extends Controller
{
    /**
     * Display a listing of income categories.
     */
    public function index(Request $request): Response
    {
        $query = IncomeCategory::with('updatedBy')->orderBy('name_ar');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $incomeCategories = $query->paginate(20)->withQueryString();

        return Inertia::render('Settings/System/Index', [
            'income_categories' => $incomeCategories,
            'activeSection' => 'income-categories',
            'filters' => $request->only('search', 'transaction_type'),
        ]);
    }

    /**
     * Store a newly created income category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'transaction_type' => 'required|string|in:revenue,expense',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        IncomeCategory::create($validated);

        return redirect()->back()->with('success', 'تم حفظ فئة الحساب بنجاح');
    }

    /**
     * Update the specified income category.
     */
    public function update(Request $request, IncomeCategory $incomeCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'transaction_type' => 'required|string|in:revenue,expense',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $incomeCategory->update($validated);

        return redirect()->back()->with('success', 'تم تحديث فئة الحساب بنجاح');
    }

    /**
     * Remove the specified income category.
     */
    public function destroy(IncomeCategory $incomeCategory): RedirectResponse
    {
        if ($incomeCategory->hasLinkedData()) {
            return redirect()->back()->with('error', 'لا يمكن حذف فئة الحساب لوجود بيانات مرتبطة بها.');
        }

        $incomeCategory->delete();

        return redirect()->back()->with('success', 'تم حذف فئة الحساب بنجاح');
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(IncomeCategory $incomeCategory): RedirectResponse
    {
        $incomeCategory->update(['is_active' => ! $incomeCategory->is_active]);

        return redirect()->back()->with('success', 'تم تعديل حالة النشاط بنجاح');
    }
}
