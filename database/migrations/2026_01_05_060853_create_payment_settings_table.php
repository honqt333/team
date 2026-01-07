<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            
            // Gateway settings (JSON for flexibility)
            $table->json('moyasar')->nullable();  // {enabled, publishable_key, secret_key}
            $table->json('tap')->nullable();       // {enabled, public_key, secret_key}
            $table->json('paytabs')->nullable();   // {enabled, profile_id, server_key, client_key}
            
            // Bank Transfer settings
            $table->boolean('bank_transfer_enabled')->default(false);
            $table->json('bank_accounts')->nullable(); // [{bank_name, account_name, iban, account_number}]
            $table->text('bank_transfer_instructions')->nullable();
            
            // Default gateway
            $table->string('default_gateway')->default('moyasar');
            
            // Payment methods enabled
            $table->boolean('mada_enabled')->default(true);
            $table->boolean('visa_enabled')->default(true);
            $table->boolean('mastercard_enabled')->default(true);
            $table->boolean('applepay_enabled')->default(true);
            $table->boolean('stcpay_enabled')->default(false);
            
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('payment_settings')->insert([
            'default_gateway' => 'moyasar',
            'bank_transfer_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
