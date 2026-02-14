<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentsController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'file' => 'required|file|max:10240', // 10MB
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('employee-documents', 'public');

        $employee->documents()->create([
            'tenant_id' => $employee->tenant_id,
            'title' => $request->title,
            'type' => $request->type,
            'file_path' => $path,
            'expiry_date' => $request->expiry_date,
            'notes' => $request->notes,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    public function destroy(EmployeeDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', __('common.deleted_success'));
    }
}
