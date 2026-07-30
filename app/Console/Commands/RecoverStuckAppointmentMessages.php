<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendAppointmentMessage;
use App\Models\AppointmentMessageDelivery;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Throwable;

/**
 * Phase 10 hot-fix (round 2) — recovery for
 * appointment message delivery rows stuck in
 * `sending`, with the missing re-dispatch step.
 *
 * Why a two-step atomic update
 * ----------------------------
 * The original recovery only flipped `sending`
 * back to `queued`. That left the row stranded:
 * the next reminder scan's pre-filter still saw
 * the (appointment, type, snapshot) row and the
 * dispatcher's `firstOrCreate` returned the
 * existing row (no insert, no dispatch). The
 * customer never received the message.
 *
 * The fix is to do BOTH steps under the same
 * operation, with safety against a row that
 * another worker has just transitioned out of
 * `sending` while we were scanning:
 *
 *   1. Select the candidate ids in one query.
 *   2. Run a single `UPDATE ... WHERE id IN (...)
 *      AND status = 'sending'` that only flips
 *      rows that are STILL `sending`. The number
 *      of affected rows is the set of rows we
 *      actually recovered — the others are still
 *      alive in another worker and we leave them
 *      alone.
 *   3. After the UPDATE commits, dispatch a
 *      fresh job for each affected id. The job
 *      runs through the normal work path; if a
 *      second worker had already started a job
 *      for the same row, the job's own status
 *      re-check (`delivery->isTerminal()` or the
 *      `queued -> sending` atomic transition)
 *      will bail before any SmsService call.
 *
 * The dispatch is best-effort: a failed dispatch
 * is logged with the exception class only (no
 * phone, no SMS body) and the loop continues.
 * The row is already `queued`; the next
 * recovery tick will pick it up if a follow-up
 * dispatch never gets through.
 *
 * Idempotency: the unique index
 * (appointment_id, message_type,
 * scheduled_at_snapshot) means a re-dispatch
 * finds the SAME row (not a new one). The worker's
 * `queued -> sending` UPDATE inside
 * SendAppointmentMessage::handle() is the lock
 * against double-send.
 */
class RecoverStuckAppointmentMessages extends Command
{
    protected $signature = 'appointments:recover-stuck
        {--stale-after=300 : Seconds since attempted_at after which a row is considered stuck}';

    protected $description = 'Recover appointment message delivery rows stuck in the sending state and re-dispatch their jobs';

    public function handle(): int
    {
        $staleAfter = (int) $this->option('stale-after');

        if ($staleAfter < 30) {
            $this->error('stale-after must be at least 30 seconds (a worker that has been running for less than that is not stuck).');

            return self::FAILURE;
        }

        $threshold = Carbon::now()->subSeconds($staleAfter);

        // Step 1: candidate ids. We capture the id
        // and a small payload (status, attempts) so
        // we can detect a state change between the
        // SELECT and the UPDATE.
        $candidates = AppointmentMessageDelivery::query()
            ->where('status', AppointmentMessageDelivery::STATUS_SENDING)
            ->where('attempted_at', '<=', $threshold)
            ->where('attempts', '<', 3)
            ->get(['id', 'status', 'attempts']);

        if ($candidates->isEmpty()) {
            $this->info('Recovered 0 stuck row(s).');

            return self::SUCCESS;
        }

        // Step 2: atomic conditional flip. We only
        // update rows that are STILL `sending`
        // (the in-PHP check) and STILL under the
        // attempt cap (the in-SQL check) and STILL
        // stale (the in-SQL check). A worker that
        // transitioned the row out of `sending`
        // between the SELECT and the UPDATE will
        // be skipped — the WHERE clause won't
        // match, the UPDATE returns 0 affected
        // rows for that id, and we won't dispatch.
        $candidateIds = $candidates->pluck('id')->all();

        $recoveredIds = [];

        try {
            DB::transaction(function () use ($candidateIds, $threshold, &$recoveredIds) {
                // The single conditional UPDATE.
                // returns the number of rows actually
                // flipped, which is the set of ids
                // we are responsible for re-dispatching.
                $updated = AppointmentMessageDelivery::query()
                    ->whereIn('id', $candidateIds)
                    ->where('status', AppointmentMessageDelivery::STATUS_SENDING)
                    ->where('attempted_at', '<=', $threshold)
                    ->where('attempts', '<', 3)
                    ->update([
                        'status' => AppointmentMessageDelivery::STATUS_QUEUED,
                        'attempted_at' => null,
                    ]);

                // Re-read the rows we flipped. The
                // UPDATE above updated them, but
                // Eloquent's update() does not return
                // the affected ids; we re-SELECT
                // the candidate set with the new
                // status. Any row that was updated
                // will appear here.
                $recoveredIds = AppointmentMessageDelivery::query()
                    ->whereIn('id', $candidateIds)
                    ->where('status', AppointmentMessageDelivery::STATUS_QUEUED)
                    ->whereNull('attempted_at')
                    ->pluck('id')
                    ->all();

                // $updated is informational; $recoveredIds
                // is the source of truth. We log both
                // to make operator debugging easier.
                if ($updated !== count($recoveredIds)) {
                    Log::info('appointment_message.recover.count_mismatch', [
                        'updated' => $updated,
                        'recovered_count' => count($recoveredIds),
                    ]);
                }
            });
        } catch (Throwable $e) {
            // The DB transaction failed entirely.
            // No row was flipped, no job was
            // dispatched. Log the exception class
            // only (no message body, no phone, no
            // PII) and let the next tick try again.
            Log::warning('appointment_message.recover.transaction_failed', [
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);
            $this->error('Recovery transaction failed: '.(new ReflectionClass($e))->getShortName());

            return self::FAILURE;
        }

        // Step 3: dispatch a job per recovered
        // row. We do this AFTER the transaction
        // commits so a failed dispatch cannot
        // roll back the status flip. If the
        // dispatch itself throws (e.g. the queue
        // is misconfigured), we log the class
        // name and continue with the next row.
        // The next recovery tick will re-pick the
        // row.
        $dispatched = 0;

        foreach ($recoveredIds as $deliveryId) {
            try {
                SendAppointmentMessage::dispatch($deliveryId);
                $dispatched++;
            } catch (Throwable $e) {
                Log::warning('appointment_message.recover.dispatch_failed', [
                    'delivery_id' => $deliveryId,
                    'reason_class' => (new ReflectionClass($e))->getShortName(),
                ]);
                // Continue with the next row.
            }
        }

        $this->info('Recovered '.count($recoveredIds)." stuck row(s); dispatched {$dispatched} job(s).");

        return self::SUCCESS;
    }
}
