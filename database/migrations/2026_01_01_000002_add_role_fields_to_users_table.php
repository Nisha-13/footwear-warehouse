<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('shopkeeper')->after('password');
            $table->string('phone')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('designation')->nullable()->after('avatar');
            $table->foreignId('warehouse_id')->nullable()->after('designation')->constrained('warehouses')->nullOnDelete();
            $table->unsignedBigInteger('shop_id')->nullable()->after('warehouse_id');
            $table->enum('status', ['active', 'suspended'])->default('active')->after('shop_id');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn(['role', 'phone', 'avatar', 'designation', 'shop_id', 'status', 'last_login_at']);
        });
    }
};
