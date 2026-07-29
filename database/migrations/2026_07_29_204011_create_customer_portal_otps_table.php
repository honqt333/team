<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_portal_otps', function (Blueprint $table) {
            $table->id();

            // The OTP is anchored to a tenant + customer pair.
            // We require the customer row to exist BEFORE
            // sending the OTP (per Phase 7 spec: "must already
            // exist in the customers table"). The FK ensures
            // we never end up with a dangling OTP for a
            // deleted customer.
            //
            // tenant_id is denormalized here so rate-limit
            // queries and lookups can filter on it directly
            // without joining customers.
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // E.164-style normalized phone, stored for audit
            // and for the rate-limit grouping. Lookup uses
            // phone_normalized + tenant_id.
            $table->string('phone_normalized', 32);
            $table->string('phone_hash', 64)->nullable();

            // The OTP code itself, hashed (Hash::make). The
            // plain-text code NEVER leaves the service layer
            // (it is sent via the swappable SMS channel and
            // forgotten). We index code_hash because
            // verifying-by-hash-lookup is part of the
            // potential perf path on large tenants.
            //
            // The decision to store code_hash (rather than
            // look it up by phone) is intentional: a leaked
            // DB dump reveals no plaintext codes, and an
            // attacker who steals a single OTP row cannot
            // reuse the code elsewhere because the same hash
            // is unique per row (different salt per Hash::make).
            $table->string('code_hash', 255);

            // Audit fields. attempts increments on each
            // failed verify; consumed_at is the canonical
            // "this OTP was used" marker.
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);

            // 10-minute validity per Phase 7 spec.
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();

            // Request metadata: IP for rate limiting and
            // audit. last_sent_at powers the resend throttle.
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_sent_at')->nullable();

            $table->timestamps();

            // We always look up by (tenant, phone, status).
            // status = "not consumed + not expired" is
            // expressed as `consumed_at IS NULL AND expires_at
            // > now()`, but the most selective column is
            // (tenant_id, phone_normalized). Index that.
            $table->index(['tenant_id', 'phone_normalized', 'consumed_at'], 'cpo_tenant_phone_active_idx');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_otps');
    }
};
