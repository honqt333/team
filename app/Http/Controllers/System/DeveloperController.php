<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\Developer\AuditOrchestrator;
use App\Models\Developer\AuditSnapshot;
use App\Models\Developer\AuditViolation;
use App\Models\Developer\SlowQueryLog;
use App\Models\Developer\ComponentStat;
use App\Models\Developer\AiMemory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

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
     */
    public function getGraph()
    {
        // Static Mocked/Constructed Nodes and Edges based on Knowledge Graph Scanner Design
        $nodes = [
            ['id' => 'WorkOrders', 'group' => 1, 'label' => 'Work Orders Module'],
            ['id' => 'Inventory', 'group' => 2, 'label' => 'Inventory Module'],
            ['id' => 'Vehicles', 'group' => 2, 'label' => 'Vehicles Module'],
            ['id' => 'Customers', 'group' => 2, 'label' => 'Customers Module'],
            ['id' => 'Invoices', 'group' => 3, 'label' => 'Invoices & Payments'],
            ['id' => 'Notifications', 'group' => 4, 'label' => 'Alerts & Messages'],
        ];

        $links = [
            ['source' => 'WorkOrders', 'target' => 'Inventory', 'value' => 2],
            ['source' => 'WorkOrders', 'target' => 'Vehicles', 'value' => 2],
            ['source' => 'WorkOrders', 'target' => 'Customers', 'value' => 2],
            ['source' => 'WorkOrders', 'target' => 'Invoices', 'value' => 5],
            ['source' => 'WorkOrders', 'target' => 'Notifications', 'value' => 1],
            ['source' => 'Invoices', 'target' => 'Notifications', 'value' => 1],
        ];

        return response()->json([
            'nodes' => $nodes,
            'links' => $links,
        ]);
    }

    /**
     * Ask the AI Advisor for refactoring and optimizations suggestions.
     */
    public function aiAdvice(Request $request)
    {
        $latestSnapshot = AuditSnapshot::orderBy('created_at', 'desc')->first();
        if (!$latestSnapshot) {
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
                'impact' => "Affects maintenance, testing speed, and potentially violates system standards.",
                'risk' => ucfirst($v->severity),
                'solution' => "Extract logic from {$v->file_path} into a dedicated Action class or separate small services, keeping controllers under 500 lines.",
                'alternative' => "Use Eloquent Observers to hook events rather than writing inline controller code.",
                'plan' => [
                    "1. Create app/Actions/Split" . basename($v->file_path, '.php') . ".php",
                    "2. Move business logic from controller method to execute() inside the Action.",
                    "3. Inject and call Action from controller.",
                ],
                'tests' => "Run php artisan test and check controller endpoint feature test.",
                'rollback' => "Git checkout HEAD file to revert changes."
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
