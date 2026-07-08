<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CustomerImportExportController extends Controller
{
    use AuthorizesRequests;

    /**
     * Export customers to XLSX.
     */
    public function export()
    {
        $this->authorize('viewAny', Customer::class);

        $type = request('type');
        $filename = 'customers_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CustomersExport($type),
            $filename
        );
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CustomersTemplateExport(),
            'customers_template.xlsx'
        );
    }

    /**
     * Import customers from XLSX/CSV.
     */
    public function import(): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $file = request()->file('file');
        
        if (!$file) {
            return response()->json(['message' => 'لم يتم رفع ملف'], 400);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['message' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: XLSX, XLS, CSV'], 400);
        }

        try {
            $import = new \App\Imports\CustomersImport();
            $import->import($file);

            return response()->json([
                'imported' => $import->getImportedCount(),
                'errors' => $import->getImportErrors(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer Import Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage(),
            ], 500);
        }
    }
}
