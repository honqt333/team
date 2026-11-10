<?php

// Run from the project root. Creates and drops ONLY its own randomly named database.
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
use App\Models\Center;
use App\Models\Customer;
use App\Models\InternalNotification;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

config(['app.env' => 'testing', 'cache.default' => 'array', 'mail.default' => 'array', 'queue.default' => 'sync', 'session.driver' => 'array']);
$app->instance('env', 'testing');
function check(bool $ok, string $message): void
{
    if (! $ok) {
        throw new RuntimeException($message);
    }
}
$worker = ($argv[1] ?? '') === 'worker';

if ($worker) {
    [$file,$mode,$id,$run] = $argv;
    // Arguments are: worker, invoice|period, worker-number, temporary-state-directory.
    $mode = $argv[2];
    $id = $argv[3];
    $run = $argv[4];
    $state = json_decode(file_get_contents($run.'/state.json'), true, 512, JSON_THROW_ON_ERROR);
    check((bool) preg_match('/^khidmh_race_[a-f0-9]{16}$/', $state['database']), 'Unsafe database name');
    config(['database.default' => 'mysql', 'database.connections.mysql.database' => $state['database']]);
    DB::purge('mysql');
    touch($run.'/'.$mode.'-'.$id.'.ready');
    $deadline = microtime(true) + 20;

    while (! file_exists($run.'/'.$mode.'.go')) {
        check(microtime(true) < $deadline, 'Barrier timed out');
        usleep(10000);
    }
    $action = $id === '1' ? 'approve' : 'reject';
    $request = Request::create('/view/quote/'.$state['uuid'].'/'.$action, 'POST', ['rejection_reason' => 'Test decision']);
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    check($response->getStatusCode() === 302, 'Decision request status: '.$response->getStatusCode());
    $result = $request->session()->has('success') ? 'accepted' : 'rejected';
    $kernel->terminate($request, $response);
    file_put_contents($run.'/'.$mode.'-'.$id.'.result', $result);
    exit(0);
}
$database = 'khidmh_race_'.bin2hex(random_bytes(8));
$run = sys_get_temp_dir().'/'.$database;
mkdir($run, 0700);
$c = config('database.connections.mysql');
$dsn = ! empty($c['unix_socket']) ? 'mysql:unix_socket='.$c['unix_socket'] : 'mysql:host='.$c['host'].';port='.$c['port'];
$pdo = new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;

try {
    $pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database]);
    DB::purge('mysql');
    check(Artisan::call('migrate', ['--force' => true]) === 0, 'Isolated MySQL migrations failed');
    $tenant = Tenant::factory()->create();
    $center = Center::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
    $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'customer_id' => $customer->id]);
    $quote = Quote::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'created_by' => $user->id, 'status' => 'sent', 'code' => 'MYSQL-DECISION', 'valid_until' => now()->addDay()]);
    file_put_contents($run.'/state.json', json_encode(['database' => $database, 'uuid' => $quote->uuid]));

    foreach (['quote'] as $mode) {
        $processes = [];

        foreach ([1, 2] as $id) {
            $processes[] = proc_open([PHP_BINARY, __FILE__, 'worker', $mode, (string) $id, $run], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $run.'/'.$mode.'-'.$id.'.log', 'w'], 2 => ['file', $run.'/'.$mode.'-'.$id.'.log', 'a']], $pipes, getcwd());
        }
        $deadline = microtime(true) + 20;

        while (! file_exists($run.'/'.$mode.'-1.ready') || ! file_exists($run.'/'.$mode.'-2.ready')) {
            check(microtime(true) < $deadline, 'Workers failed to reach barrier');
            usleep(10000);
        }
        touch($run.'/'.$mode.'.go');

        foreach ($processes as $process) {
            check(proc_close($process) === 0, 'Worker failed');
        }
        check(file_exists($run.'/'.$mode.'-1.result') && file_exists($run.'/'.$mode.'-2.result'), 'Workers did not finish; inspect their fixture logs');
        $results = [file_get_contents($run.'/'.$mode.'-1.result'), file_get_contents($run.'/'.$mode.'-2.result')];
        sort($results);
        check($results === ['accepted', 'rejected'], 'Race accepted both requests');
        echo $mode.' simultaneous requests: one accepted, one rejected: PASS'.PHP_EOL;
    }
    check(in_array($quote->fresh()->status, ['approved', 'rejected'], true), 'Unexpected quote status');
    check(InternalNotification::where('user_id', $user->id)->whereIn('type', ['quote.approved', 'quote.rejected'])->count() === 1, 'Decision notification duplicated');
    echo 'One terminal decision and one notification: PASS'.PHP_EOL;
} finally {
    DB::disconnect('mysql');

    if ($created) {
        $pdo->exec('DROP DATABASE `'.$database.'`');
        echo 'Isolated test database removed'.PHP_EOL;
    }
}
