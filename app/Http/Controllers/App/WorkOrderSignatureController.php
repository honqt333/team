<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkOrderSignatureController extends Controller
{
    /**
     * Store a digital signature for a work order.
     */
    public function store(Request $request, WorkOrder $workOrder)
    {
        $request->validate([
            'type' => 'required|in:reception,delivery',
            'signature' => 'required|string', // Base64 image
        ]);

        $type = $request->input('type');
        $signatureData = $request->input('signature');

        // Extract base64 content
        if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $typeMatch)) {
            $data = substr($signatureData, strpos($signatureData, ',') + 1);
            $extension = strtolower($typeMatch[1]); // png, jpg, etc.
            $data = base64_decode($data);

            if ($data === false) {
                return back()->with('error', 'Invalid signature data.');
            }
        } else {
            return back()->with('error', 'Invalid signature format.');
        }

        // Generate filename and path
        $filename = "signature_{$type}_" . Str::random(10) . ".{$extension}";
        $path = "work_orders/{$workOrder->id}/signatures/{$filename}";

        // Store file
        Storage::disk('public')->put($path, $data);

        // Update work order
        $field = "{$type}_signature";
        $dateField = "{$type}_signed_at";

        // Delete old signature if exists
        if ($workOrder->$field) {
            Storage::disk('public')->delete($workOrder->$field);
        }

        $workOrder->update([
            $field => $path,
            $dateField => now(),
        ]);

        $workOrder->logActivity("signed_{$type}", __("work_orders.activities.actions.signed_{$type}"));

        return back()->with('success', __('common.saved_success'));
    }
}
