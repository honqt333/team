<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Storage\TenantAttachmentsService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentManagementController extends Controller
{
    public function __construct(
        private readonly TenantAttachmentsService $attachmentsService
    ) {}

    /**
     * Standalone permission-gated system attachments management page.
     */
    public function index(Request $request): Response
    {
        $this->authorize(Permissions::ATTACHMENTS_VIEW);

        $user = $request->user();
        $filters = $request->only(['source', 'search']);
        $page = max(1, (int) $request->query('page', 1));

        $payload = $this->attachmentsService->paginateForUser(
            user: $user,
            filters: $filters,
            perPage: 15,
            page: $page,
            path: route('settings.attachments.index')
        );

        return Inertia::render('Settings/System/Attachments', [
            'storage_usage' => $user->tenant?->storage_usage,
            'attachments' => $payload['items']->toArray(),
            'attachmentSummary' => $payload['summary'],
            'attachmentFilters' => [
                'source' => (string) ($filters['source'] ?? ''),
                'search' => (string) ($filters['search'] ?? ''),
            ],
            'canDeleteAttachments' => $user->can(Permissions::ATTACHMENTS_DELETE),
        ]);
    }

    /**
     * Authorized file download stream (protected against inline XSS rendering).
     */
    public function download(Request $request, string $source, int $id): StreamedResponse
    {
        $this->authorize(Permissions::ATTACHMENTS_VIEW);

        return $this->attachmentsService->downloadForUser($request->user(), $source, $id);
    }

    /**
     * Authorized deletion of an attachment with activity logging & quota cache clear.
     */
    public function destroy(Request $request, string $source, int $id): RedirectResponse
    {
        $this->authorize(Permissions::ATTACHMENTS_DELETE);

        $this->attachmentsService->deleteForUser($request->user(), $source, $id);

        return back()->with('success', __('system_settings.attachments.delete_success'));
    }
}
