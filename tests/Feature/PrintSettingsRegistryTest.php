<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use App\Support\PrintDocumentRegistry;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PrintSettingsRegistryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PermissionsSeeder::class);

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $role = Role::firstOrCreate(
            [
                'name' => 'super_admin',
                'guard_name' => 'web',
                'tenant_id' => $this->tenant->id,
            ],
            [
                'label_ar' => 'مدير عام',
                'label_en' => 'Super Admin',
                'description' => 'Tenant administrator',
            ]
        );
        $role->syncPermissions([Permissions::COMPANY_MANAGE]);
        $this->user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->refresh();
    }

    private function makeValidPng(string $name = 'sig.png'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sig_reg_');
        $im = imagecreatetruecolor(8, 8);
        imagepng($im, $tmp);
        imagedestroy($im);

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    public function test_print_settings_exposes_only_real_operational_documents(): void
    {
        $response = $this->actingAs($this->user)->get(route('settings.print'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Print/Index')
            ->has('document_meta', count(PrintDocumentRegistry::keys()))
            ->has('print_settings.documents', count(PrintDocumentRegistry::keys()))
            ->has('print_settings.documents', fn (Assert $docs) => $docs
                ->hasAll(PrintDocumentRegistry::keys())
                ->missingAll([
                    'subscription_invoice',
                    'delivery_note',
                    'receipt',
                    'customers',
                    'vehicles',
                    'payroll',
                    'attendance',
                    'employee',
                    'payslip',
                ])
                ->etc()
            )
        );

        $documents = $response->viewData('page')['props']['print_settings']['documents'];
        $this->assertSame(PrintDocumentRegistry::keys(), array_keys($documents));
        $this->assertSame(
            ['print_terms', 'terms_first_page', 'show_stamp', 'show_customer_address'],
            $response->viewData('page')['props']['document_meta']['condition_report']['options']
        );
    }

    public function test_print_settings_reads_legacy_quote_key_under_quotation(): void
    {
        $this->tenant->update([
            'print_settings' => [
                'documents' => [
                    'quote' => [
                        'title_ar' => 'عرض سعر مخصص قديم',
                        'title_en' => 'Legacy Custom Quote',
                        'show_iban' => true,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)->get(route('settings.print'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('print_settings.documents.quotation.title_ar', 'عرض سعر مخصص قديم')
            ->where('print_settings.documents.quotation.title_en', 'Legacy Custom Quote')
            ->where('print_settings.documents.quotation.show_iban', true)
        );
    }

    public function test_update_strips_non_operational_keys_and_normalizes_legacy_aliases(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.system.update'), [
            'section' => 'print',
            'visual' => [
                'active_template' => 'TemplateDefaultA4',
                'show_logo' => true,
                'primary_color' => '#fbbf24',
                'footer_text' => 'Footer',
            ],
            'documents' => [
                'invoice' => ['title_ar' => 'فاتورة محدثة', 'title_en' => 'Updated Invoice'],
                'quote' => ['title_ar' => 'عرض سعر محدث', 'title_en' => 'Updated Quote'],
                'subscription_invoice' => ['title_ar' => 'Should Be Stripped'],
                'delivery_note' => ['title_ar' => 'Should Be Stripped'],
                'receipt' => ['title_ar' => 'Should Be Stripped'],
                'customers' => ['title_ar' => 'Should Be Stripped'],
            ],
        ]);

        $response->assertRedirect();

        $this->tenant->refresh();
        $savedDocs = $this->tenant->print_settings['documents'] ?? [];

        $this->assertArrayHasKey('invoice', $savedDocs);
        $this->assertArrayHasKey('quotation', $savedDocs);
        $this->assertSame('عرض سعر محدث', $savedDocs['quotation']['title_ar']);

        $this->assertArrayNotHasKey('quote', $savedDocs);
        $this->assertArrayNotHasKey('subscription_invoice', $savedDocs);
        $this->assertArrayNotHasKey('delivery_note', $savedDocs);
        $this->assertArrayNotHasKey('receipt', $savedDocs);
        $this->assertArrayNotHasKey('customers', $savedDocs);
    }

    public function test_signature_upload_accepts_all_canonical_documents_and_rejects_excluded_types(): void
    {
        foreach (PrintDocumentRegistry::keys() as $docType) {
            $res = $this->actingAs($this->user)->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng("{$docType}.png"),
                'name_ar' => "توقيع {$docType}",
                'name_en' => "Sig {$docType}",
                'document_type' => $docType,
            ]);

            $res->assertOk();
            $res->assertJsonFragment(['document_type' => $docType]);
        }

        // Legacy alias 'quote' normalizes to canonical 'quotation'.
        $aliasRes = $this->actingAs($this->user)->postJson(route('settings.print.signatures.store'), [
            'signature' => $this->makeValidPng('legacy-quote.png'),
            'name_ar' => 'توقيع قديم',
            'name_en' => 'Legacy Alias Sig',
            'document_type' => 'quote',
        ]);
        $aliasRes->assertOk();
        $aliasRes->assertJsonFragment(['document_type' => 'quotation']);

        // Excluded platform/phantom types are rejected with 422.
        foreach (['subscription_invoice', 'delivery_note', 'customers', 'vehicles'] as $disallowed) {
            $fail = $this->actingAs($this->user)->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng("{$disallowed}.png"),
                'name_ar' => 'مرفوض',
                'name_en' => 'Rejected',
                'document_type' => $disallowed,
            ]);

            $fail->assertStatus(422);
            $fail->assertJsonValidationErrors(['document_type']);
        }
    }
}
