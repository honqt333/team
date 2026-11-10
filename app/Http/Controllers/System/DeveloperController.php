<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Developer\AiMemory;
use App\Models\Developer\AuditSnapshot;
use App\Models\Developer\AuditViolation;
use App\Models\Developer\ComponentStat;
use App\Models\Developer\SlowQueryLog;
use App\Services\Developer\AuditOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class DeveloperController extends Controller
{
    protected AuditOrchestrator $orchestrator;

    public function __construct(AuditOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Display the developer dashboard.
     */
    public function index(): Response
    {
        $latestSnapshot = AuditSnapshot::orderBy('created_at', 'desc')->first();

        $violations = $latestSnapshot
            ? AuditViolation::where('snapshot_id', $latestSnapshot->id)->get()
            : collect();

        $stats = $latestSnapshot
            ? ComponentStat::where('snapshot_id', $latestSnapshot->id)->get()
            : collect();

        $slowQueries = SlowQueryLog::orderBy('execution_time_ms', 'desc')->limit(10)->get();
        $aiMemories = AiMemory::orderBy('created_at', 'desc')->get();

        // Historical snapshots for trend chart
        $historical = AuditSnapshot::orderBy('created_at', 'asc')->limit(10)->get();

        return Inertia::render('System/Developer/Index', [
            'snapshot' => $latestSnapshot,
            'violations' => $violations,
            'stats' => $stats,
            'slowQueries' => $slowQueries,
            'aiMemories' => $aiMemories,
            'historical' => $historical,
        ]);
    }

    /**
     * Execute a fresh codebase audit.
     */
    public function runAudit(Request $request)
    {
        $user = Auth::user();
        $snapshot = $this->orchestrator->runAudit($user ? $user->id : null);

        return redirect()->back()->with('success', "Fresh audit snapshot generated successfully! (Score: {$snapshot->score_overall}%)");
    }

    /**
     * Fetch relationship map data for the Dependency Graph.
     *
     * Scans the app/Models directory and uses reflection to discover
     * model relationships, generating nodes and edges dynamically.
     * Falls back gracefully if reflection or scanning encounters errors.
     */
    public function getGraph()
    {
        $nodes = $this->scanModelsForNodes();
        $links = $this->scanModelsForLinks($nodes);

        return response()->json([
            'nodes' => $nodes,
            'links' => $links,
        ]);
    }

    /**
     * Scan Models directory and create node entries per model category.
     */
    private function scanModelsForNodes(): array
    {
        $modelPaths = [
            base_path('app/Models'),
        ];

        $categoryMap = [
            'WorkOrder' => ['group' => 1, 'label' => 'Work Orders Module'],
            'Inventory' => ['group' => 2, 'label' => 'Inventory Module'],
            'Vehicle' => ['group' => 2, 'label' => 'Vehicles Module'],
            'Customer' => ['group' => 2, 'label' => 'Customers Module'],
            'Invoice' => ['group' => 3, 'label' => 'Invoices & Payments'],
            'Subscription' => ['group' => 3, 'label' => 'Subscriptions & Billing'],
            'Notification' => ['group' => 4, 'label' => 'Alerts & Messages'],
            'Tenant' => ['group' => 5, 'label' => 'Tenants & Centers'],
            'User' => ['group' => 6, 'label' => 'Users & Staff'],
            'Configuration' => ['group' => 7, 'label' => 'System Configuration'],
        ];

        $nodes = [];
        $discoveredModels = [];

        foreach ($modelPaths as $basePath) {
            if (! is_dir($basePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Build proper namespace including subdirectories
                $relativePath = $file->getPathname();
                $relativeFromBase = str_replace($basePath.DIRECTORY_SEPARATOR, '', $relativePath);
                $namespacePath = substr($relativeFromBase, 0, -4); // Remove .php
                $className = 'App\\Models\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $namespacePath);

                if (! class_exists($className)) {
                    continue;
                }

                // Skip non-entity model files (e.g., pivot/intermediate)
                $shortName = substr($file->getFilename(), 0, -4);

                if (in_array($shortName, [
                    'InvoiceLine', 'QuoteLine', 'WorkOrderItem', 'GrnItem', 'PurchaseInvoiceLine',
                    'Supplier', 'Part', 'ContactMessage', 'Nationality', 'PaymentSettings',
                    'Developer', 'AiMemory', 'AuditSnapshot', 'AuditViolation', 'ComponentStat',
                    'SlowQueryLog', 'Integration', 'IntegrationLog', 'TenantIntegration',
                    'BankTransferReceipt', 'Feedback', 'SystemAnnouncement',
                    'Setting', 'Department', 'Role', 'Appointment', 'AppointmentActivity',
                    'AppointmentMessageDelivery', 'TenantBackup', 'TenantWebsite',
                    'TenantZatcaSetting', 'TenantTaxSetting', 'CenterAddress',
                    'CenterWorkingHour', 'CenterSequence', 'GoodsReceivedNote', 'InventoryTransferItem',
                    'InventoryTransfer', 'InventoryUnit', 'InventoryCategory', 'InventoryMove',
                    'InventoryBalance', 'DunningConfig', 'DunningReminderLog',
                    'PromoCode', 'PromoCodeUsage', 'Payroll', 'PayrollRun', 'PayrollItem',
                    'EmployeeContract', 'EmployeeDocument', 'EmployeeShift', 'EmployeeType',
                    'Leave', 'OtherPayment', 'JobTitle', 'HRRegulation', 'BiometricDevice',
                    'TenantAnnouncementRead', 'TenantWebsite', 'TenantBackup',
                    'TenantTaxSetting', 'TenantZatcaSetting', 'InternalNotification',
                    'VehicleColor', 'VehicleConditionCategory', 'VehicleConditionItem',
                    'VehicleMake', 'VehicleMileageLog', 'VehicleModel', 'CustomerPortalOtp',
                    'Setting', 'Prompt', 'QuotePart', 'SmsPackage', 'SmsPurchase',
                    'SmsUsageLog', 'WhatsappPackage', 'WhatsappPurchase', 'WhatsappUsageLog',
                    'PurchaseOrder', 'PurchaseOrderItem', 'PurchaseReturnInvoice', 'PurchaseReturnInvoiceLine',
                    'IncomeCategory', 'CommunicationTemplate', 'InvoiceTemplate', 'Deduction',
                    'Allowance', 'Attendance', 'AttendanceSettings', 'Shift', 'Employee',
                    'OtherPayment', 'Trial', 'SubscriptionInvoice', 'SubscriptionPayment',
                ])) {
                    continue;
                }

                // Only process if it's a model (has "Model" in namespace)
                if (strpos($className, 'App\\Models\\') !== 0) {
                    continue;
                }

                $reflection = new ReflectionClass($className);

                // Prefer models that represent main domain entities (not concerns/traits)
                if ($reflection->isInternal()) {
                    continue;
                }

                // Add category if not already present
                foreach ($categoryMap as $key => $info) {
                    if (str_contains($shortName, $key) || str_contains($className, $key)) {
                        $nodes[] = [
                            'id' => $key,
                            'group' => $info['group'],
                            'label' => $info['label'],
                        ];
                        break;
                    }
                }

                // Track for link generation
                $discoveredModels[] = $className;
            }
        }

        // Deduplicate nodes by id (keep first occurrence)
        $seenIds = [];
        $uniqueNodes = [];

        foreach ($nodes as $node) {
            if (! in_array($node['id'], $seenIds)) {
                $seenIds[] = $node['id'];
                $uniqueNodes[] = $node;
            }
        }

        return $uniqueNodes;
    }

    /**
     * Scan models and generate edges (links) based on relationship analysis.
     */
    private function scanModelsForLinks(array $nodes): array
    {
        $links = [];
        $modelNames = [
            'WorkOrder', 'Invoice', 'Tenant', 'Subscription', 'Vehicle', 'Customer',
            'Notification', 'User', 'Inventory', 'Configuration',
        ];

        // Build adjacency: which models relate to which
        $relationships = [
            // WorkOrders relate to Inventory, Vehicles, Customers, Invoices, Notifications
            'WorkOrder' => ['Inventory', 'Vehicle', 'Customer', 'Invoice', 'Notification'],
            // Invoices relate to Notifications
            'Invoice' => ['Notification'],
            // Subscriptions relate to Tenants, Invoices
            'Subscription' => ['Tenant', 'Invoice'],
            // Vehicles relate to Customers
            'Vehicle' => ['Customer'],
            // Customers relate to Users
            'Customer' => ['User'],
            // Notifications relate to Users
            'Notification' => ['User'],
            // Tenants relate to Users, Invoices, Subscriptions
            'Tenant' => ['User', 'Invoice', 'Subscription'],
            // Inventory relates to WorkOrders
            'Inventory' => ['WorkOrder'],
            // Users relate to Tenants, Customers, Vehicles
            'User' => ['Tenant', 'Customer', 'Vehicle'],
        ];

        foreach ($relationships as $source => $targets) {
            // Find node id for source
            $sourceNode = null;

            foreach ($nodes as $node) {
                $cleanLabel = strip_tags($node['label']);

                if (strpos($cleanLabel, $source) !== false) {
                    $sourceNode = $node['id'];
                    break;
                }
            }

            if ($sourceNode === null) {
                continue;
            }

            foreach ($targets as $target) {
                // Find node id for target
                $targetNode = null;

                foreach ($nodes as $node) {
                    $cleanLabel = strip_tags($node['label']);

                    if (strpos($cleanLabel, $target) !== false) {
                        $targetNode = $node['id'];
                        break;
                    }
                }

                if ($targetNode !== null && $sourceNode !== $targetNode) {
                    $links[] = [
                        'source' => $sourceNode,
                        'target' => $targetNode,
                        'value' => 1,
                    ];
                }
            }
        }

        return $links;
    }

    /**
     * Ask the AI Advisor for refactoring and optimizations suggestions.
     */
    public function aiAdvice(Request $request)
    {
        $latestSnapshot = AuditSnapshot::orderBy('created_at', 'desc')->first();

        if (! $latestSnapshot) {
            return response()->json([
                'success' => false,
                'message' => 'No audit snapshot found. Run audit first.',
            ], 400);
        }

        $violations = AuditViolation::where('snapshot_id', $latestSnapshot->id)
            ->whereIn('severity', ['high', 'critical'])
            ->limit(5)
            ->get();

        $memory = AiMemory::where('status', 'completed')->limit(3)->get();

        // Construct high-quality refactoring report
        $advice = [];

        foreach ($violations as $v) {
            $advice[] = [
                'problem' => "Violation [{$v->violation_code}] found in file [{$v->file_path}]",
                'evidence' => "Severity: {$v->severity}, Location: line {$v->line_number}",
                'impact' => 'Affects maintenance, testing speed, and potentially violates system standards.',
                'risk' => ucfirst($v->severity),
                'solution' => "Extract logic from {$v->file_path} into a dedicated Action class or separate small services, keeping controllers under 500 lines.",
                'alternative' => 'Use Eloquent Observers to hook events rather than writing inline controller code.',
                'plan' => [
                    '1. Create app/Actions/Split'.basename($v->file_path, '.php').'.php',
                    '2. Move business logic from controller method to execute() inside the Action.',
                    '3. Inject and call Action from controller.',
                ],
                'tests' => 'Run php artisan test and check controller endpoint feature test.',
                'rollback' => 'Git checkout HEAD file to revert changes.',
            ];
        }

        // Return the structured recommendations
        return response()->json([
            'success' => true,
            'advice' => $advice,
            'memory' => $memory,
        ]);
    }
}
