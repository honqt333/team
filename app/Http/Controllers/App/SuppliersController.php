<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\App\Purchasing\SupplierRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = auth()->user()->tenant_id;
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        // Filter by user's current center unless super admin wants to see all (and didnt filter)
        // But usually, 'Not isolated' complaint suggests strict isolation.
        // I will use current_center_id logic similar to Employee.
        $centerId = $isSuperAdmin ? $request->center_id : auth()->user()->current_center_id;

        $query = Supplier::forTenant($tenantId)
            ->when(!$isSuperAdmin || $centerId, fn($q) => $q->forCenter($centerId))
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->withCount('purchaseOrders')
            ->orderBy('name');

        $suppliers = $query->paginate(25)->withQueryString();

        // Add placeholder balance
        $suppliers->getCollection()->transform(function ($supplier) {
            $supplier->balance = 0; // Placeholder
            return $supplier;
        });

        return Inertia::render('Purchasing/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);
        
        $supplier->loadCount('purchaseOrders');
        
        // Placeholders for future modules
        $counts = [
            'orders' => $supplier->purchase_orders_count,
            'invoices' => 0,
            'payments' => 0,
        ];

        return Inertia::render('Purchasing/Suppliers/Show', [
            'supplier' => $supplier,
            'counts' => $counts,
            'balance' => 0, // Placeholder
        ]);
    }



    public function store(SupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);

        $validated = $request->validated();
        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['center_id'] = auth()->user()->current_center_id;

        Supplier::create($validated);

        return redirect()->route('app.purchasing.suppliers.index')
            ->with('success', __('purchasing.suppliers.created'));
    }



    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return redirect()->back()->with('success', __('purchasing.suppliers.updated'));
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', __('common.cannot_delete_system_data'));
        }

        $supplier->delete();

        return redirect()->route('app.purchasing.suppliers.index')
            ->with('success', __('common.deleted_success'));
    }

    public function toggleActive(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => !$supplier->is_active]);

        return back()->with('success', $supplier->is_active 
            ? __('purchasing.suppliers.activated') 
            : __('purchasing.suppliers.deactivated'));
    }

    /**
     * API: Search suppliers for autocomplete.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $suppliers = Supplier::forTenant($tenantId)
            ->forCenter($centerId)
            ->active()
            ->search($request->input('q'))
            ->select('id', 'name', 'code', 'phone')
            ->limit(20)
            ->get();

        return response()->json($suppliers);
    }

    /**
     * Export suppliers to Excel (XLSX).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        // Set locale from request if provided
        if ($request->has('locale') && in_array($request->input('locale'), ['ar', 'en'])) {
            app()->setLocale($request->input('locale'));
        }

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $suppliers = Supplier::forTenant($tenantId)
            ->forCenter($centerId)
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'phone', 'email', 'type', 'tax_number', 'is_active']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set RTL for Arabic
        $sheet->setRightToLeft(app()->getLocale() === 'ar');

        // Headers
        $headers = [
            '#',
            __('purchasing.suppliers.name'),
            __('purchasing.suppliers.code'),
            __('purchasing.suppliers.phone'),
            __('purchasing.suppliers.email'),
            __('purchasing.suppliers.type'),
            __('purchasing.suppliers.tax_number'),
            __('common.status'),
        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Style headers
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E5E7EB');

        // Data
        $row = 2;
        foreach ($suppliers as $index => $supplier) {
            $sheet->fromArray([
                $index + 1,
                $supplier->name,
                $supplier->code,
                $supplier->phone,
                $supplier->email,
                $supplier->type === 'parts' ? __('purchasing.suppliers.type_parts') : __('purchasing.suppliers.type_services'),
                $supplier->tax_number,
                $supplier->is_active ? __('common.active') : __('common.inactive'),
            ], null, 'A' . $row);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'suppliers_' . date('Y-m-d_His') . '.xlsx';
        
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
