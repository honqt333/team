<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfer_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_payment_id')->nullable()->constrained('subscription_payments')->nullOnDelete();

            // Transfer details (as filled by tenant)
            $table->decimal('transfer_amount', 10, 2);
            $table->string('transfer_reference', 100)->nullable(); // bank reference number
            $table->date('transfer_date');
            $table->string('bank_name', 100)->nullable();
            $table->string('sender_account_name', 200)->nullable();
            $table->string('sender_account_number', 50)->nullable();

            // Uploaded receipt
            $table->string('receipt_path', 500);
            $table->string('receipt_filename', 255)->nullable();
            $table->string('receipt_mime_type', 50)->nullable();
            $table->unsignedInteger('receipt_size_bytes')->nullable();

            // Review
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfer_receipts');
    }
};
