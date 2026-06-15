<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final resolution of the debit-note / refund accounting issue.
     *
     * Decision: a debit note is a BOOKKEEPING note that lives on the
     * PurchaseReturnInvoice (create_debit_note / debit_note_date). It is
     * NOT an actual cash movement and therefore has no row in the
     * `payments` table.
     *
     * History of the data:
     *   - Pre-fix rows:   type='refund' + payment_method='debit_note'
     *     These were created by the old buggy `recordReturn()` path.
     *     They polluted Supplier::calculateBalance() because the
     *     cashRefunds filter excluded them by payment_method, but the
     *     refund-totals elsewhere still saw them.
     *
     *   - Mid-fix rows:   type='debit_note' + payment_method='debit_note'
     *     Created by the first migration in this fix, which re-classified
     *     the pre-fix rows. Then the application code was changed to NOT
     *     write such rows at all, so they have no semantic meaning now.
     *
     * Cleanup: delete BOTH categories. The original invoice balance and
     * the supplier balance are already correct because the return's
     * total has always been deducted from the invoice balance, and the
     * PurchaseReturnInvoice still carries the create_debit_note flag and
     * debit_note_date that document the bookkeeping decision.
     *
     * This migration is safe to run on a database that contains both
     * categories — the WHERE clauses only touch the misclassified rows.
     */
    public function up(): void
    {
        // Widen the type enum to include 'debit_note' so that the
        // mid-fix rows are accepted. We keep this wide enum around
        // because (a) it is harmless — application code only writes
        // 'payment' and 'refund', and (b) some installations may have
        // live data in this category that we are about to delete.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY type ENUM('payment', 'refund', 'debit_note') NOT NULL DEFAULT 'payment'");
        }
        // SQLite (used in tests) stores the column as plain text — no
        // ALTER needed.

        // Delete the misclassified rows. The supplier balance and the
        // invoice balance are already correct without them, because:
        //   1. The return's total is deducted from the invoice balance
        //      (PurchaseInvoicesController::recordReturn, line: $newBalance
        //      = max(0, $purchaseInvoice->balance - $total)).
        //   2. The PurchaseReturnInvoice still carries create_debit_note
        //      + debit_note_date + notes explaining the bookkeeping
        //      decision.
        //   3. The user's Refund (cash) payments, if any, are still in
        //      the payments table with type='refund', and they continue
        //      to flow into the cash-refund totals correctly.
        $deletedPreFix = DB::table('payments')
            ->where('type', 'refund')
            ->where('payment_method', 'debit_note')
            ->delete();

        $deletedMidFix = DB::table('payments')
            ->where('type', 'debit_note')
            ->where('payment_method', 'debit_note')
            ->delete();

        if ($deletedPreFix > 0 || $deletedMidFix > 0) {
            \Illuminate\Support\Facades\Log::info('Debit-note cleanup', [
                'pre_fix_rows_deleted'  => $deletedPreFix,
                'mid_fix_rows_deleted'  => $deletedMidFix,
            ]);
        }
    }

    public function down(): void
    {
        // We don't try to reconstruct the deleted rows — they were
        // bookkeeping noise, not real cash movements. The down migration
        // simply re-tightens the enum so that the schema matches the
        // application code's Payment::TYPES constant.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY type ENUM('payment', 'refund') NOT NULL DEFAULT 'payment'");
        }
    }
};
