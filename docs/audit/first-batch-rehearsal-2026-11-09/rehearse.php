<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$root = __DIR__;
$db = $root.'/rehearsal.sqlite';

if (file_exists($db)) {
    throw new RuntimeException('Use a fresh rehearsal directory.');
}
touch($db);
config(['app.env' => 'testing', 'database.default' => 'sqlite', 'database.connections.sqlite.database' => $db, 'cache.default' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array', 'filesystems.disks.public.root' => $root.'/public', 'filesystems.disks.local.root' => $root.'/private']);
DB::purge();
$run = function ($command, $args = []) {
    $code = Artisan::call($command, $args);
    echo $command.': '.$code.PHP_EOL;

    if ($code !== 0) {
        echo Artisan::output();

        throw new RuntimeException($command.' failed');
    }
};
$path = 'database/migrations/2026_11_09_010000_add_single_use_employee_invitations_to_users.php';
$run('migrate', ['--force' => true]);
$run('migrate:rollback', ['--path' => $path, '--step' => 1, '--force' => true]);

if (Schema::hasColumn('users', 'invitation_token_hash')) {
    throw new RuntimeException('Rollback failed');
}
$tenant = Tenant::factory()->create();
$user = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Rehearsal account']);
$before = DB::table('users')->where('id', $user->id)->first();
$run('migrate', ['--path' => $path, '--force' => true]);
$after = DB::table('users')->where('id', $user->id)->first();

if ($before->password !== $after->password || $before->email !== $after->email || $after->invitation_token_hash !== null || $after->invitation_expires_at !== null) {
    throw new RuntimeException('Legacy user preservation failed');
}
echo 'Migration up/down and legacy account preservation: PASS'.PHP_EOL;
$public = Storage::disk('public');
$private = Storage::disk('local');
$paths = ['invoices/subscription/REHEARSAL.pdf', 'bank-transfer-receipts/1/REHEARSAL.pdf'];

foreach ($paths as $p) {
    $public->put($p, '%PDF-rehearsal-fixture');
}
$public->put('unrelated/keep.txt', 'preserve');
$run('billing:privatize-documents');

foreach ($paths as $p) {
    if (! $public->exists($p) || $private->exists($p)) {
        throw new RuntimeException('Preview mutated files');
    }
}
$run('billing:privatize-documents', ['--apply' => true]);

foreach ($paths as $p) {
    if ($public->exists($p) || $private->get($p) !== '%PDF-rehearsal-fixture') {
        throw new RuntimeException('Move verification failed');
    }
}
$run('billing:privatize-documents', ['--apply' => true]);

if ($public->get('unrelated/keep.txt') !== 'preserve') {
    throw new RuntimeException('Unrelated file changed');
}
echo 'Preview, verified transfer, idempotent rerun and unrelated file preservation: PASS'.PHP_EOL;
