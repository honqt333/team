<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Pin the contract that Policies reference permissions through
 * the `App\Support\Permissions` constants rather than magic
 * strings. This catches a class of bugs where a permission
 * string drifts away from the central registry and silently
 * becomes invalid (no error, just `$user->can('foo.bar')`
 * always returning false).
 *
 * The test reads every PHP file under app/Policies/, finds
 * `Permissions::*` references, and asserts each is a defined
 * constant on the Permissions class. It also asserts no policy
 * uses a bare string literal that looks like a permission
 * (e.g. `'crm.customers.view'`), which would be a code smell.
 */
class PolicyPermissionReferenceTest extends TestCase
{
    public function test_every_permissions_constant_referenced_in_policies_is_defined(): void
    {
        $policyDir = base_path('app/Policies');
        $found = $this->extractPermissionConstants($policyDir);

        $this->assertNotEmpty(
            $found,
            'No Permissions::* references found in Policies. Either the test is broken or the policies do not use the registry.'
        );

        $reflection = new \ReflectionClass(\App\Support\Permissions::class);
        $defined = array_keys($reflection->getConstants());

        $undefined = array_diff($found, $defined);
        $this->assertEmpty(
            $undefined,
            'Policies reference these permission constants but they are not defined on App\Support\Permissions: '
                . implode(', ', $undefined)
        );
    }

    public function test_policies_do_not_use_hardcoded_permission_strings(): void
    {
        $policyDir = base_path('app/Policies');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($policyDir)
        );

        $offenders = [];
        // Match calls like ->can('foo.bar'), hasPermissionTo('foo.bar'),
        // or the bare 'foo.bar' on the right-hand side of an authorize
        // call. The string must contain a dot to be considered a
        // permission, which rules out unrelated short strings.
        $pattern = "/->(can|hasPermissionTo|authorize)\(\s*['\"]([a-z_]+\\.[a-z_\\.]+)['\"]/i";

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            if (preg_match_all($pattern, $source, $matches)) {
                foreach ($matches[2] as $perm) {
                    $offenders[] = $file->getBasename() . ' -> "' . $perm . '"';
                }
            }
        }

        $this->assertEmpty(
            $offenders,
            "These Policies use hard-coded permission strings instead of Permissions::* constants:\n  - "
                . implode("\n  - ", $offenders)
        );
    }

    public function test_each_policy_uses_at_least_one_permissions_constant(): void
    {
        // A Policy that does not reference the permissions
        // registry is either not enforcing anything or is
        // relying on hard-coded strings (already caught by the
        // test above). Either way, it should be reviewed.
        $policyDir = base_path('app/Policies');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($policyDir)
        );

        $missing = [];
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $base = $file->getBasename('.php');

            // Skip policies that intentionally only do tenant
            // scoping without per-action permissions.
            if (in_array($base, ['GoodsReceivedNotePolicy', 'UserPolicy'], true)) {
                continue;
            }

            if (! str_contains($source, 'Permissions::')
                && ! str_contains($source, 'hasPermissionTo(')
                && ! str_contains($source, '->can(')) {
                $missing[] = $base;
            }
        }

        $this->assertEmpty(
            $missing,
            'These Policies do not reference any permission. Either add Permissions::* checks or remove them from the policy layer: '
                . implode(', ', $missing)
        );
    }

    /**
     * @return array<int, string>
     */
    private function extractPermissionConstants(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );
        $found = [];
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            if (preg_match_all('/Permissions::([A-Z_]+)/', $source, $matches)) {
                foreach ($matches[1] as $const) {
                    $found[] = $const;
                }
            }
        }
        return array_values(array_unique($found));
    }
}
