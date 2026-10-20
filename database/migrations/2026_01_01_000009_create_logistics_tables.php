<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no')->unique();
            $table->foreignId('transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['inward', 'outward'])->default('outward');
            $table->enum('status', ['assigned', 'picked_up', 'dispatched', 'in_transit', 'delivered', 'failed', 'cancelled'])->default('assigned');
            $table->unsignedInteger('sequence')->default(0);
            $table->string('drop_name')->nullable();
            $table->string('drop_address')->nullable();
            $table->decimal('drop_latitude', 10, 7)->nullable();
            $table->decimal('drop_longitude', 10, 7)->nullable();
            $table->decimal('transaction_amount', 14, 2)->default(0);
            $table->enum('payment_mode', ['full_cash', 'credit', 'logistics_line'])->default('credit');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('proof_note')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rider_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_locations');
        Schema::dropIfExists('delivery_status_histories');
        Schema::dropIfExists('deliveries');
    }
};
