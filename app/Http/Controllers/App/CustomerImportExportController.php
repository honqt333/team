<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Exports\CustomersExport;
use App\Exports\CustomersTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\CustomersImport;
use App\Models\Customer;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Log;
use Maatwebsite\Excel\Facades\Excel;

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
        $filename = 'customers_'.date('Y-m-d_His').'.xlsx';

        return Excel::download(
            new CustomersExport($type),
            $filename
        );
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new CustomersTemplateExport,
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

        if (! $file) {
            return response()->json(['message' => 'لم يتم رفع ملف'], 400);
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['message' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: XLSX, XLS, CSV'], 400);
        }

        try {
            $import = new CustomersImport;
            $import->import($file);

            return response()->json([
                'imported' => $import->getImportedCount(),
                'errors' => $import->getImportErrors(),
            ]);

        } catch (Exception $e) {
            Log::error('Customer Import Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء الاستيراد: '.$e->getMessage(),
            ], 500);
        }
    }
}
