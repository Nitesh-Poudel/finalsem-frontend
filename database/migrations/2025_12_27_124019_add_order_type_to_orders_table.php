<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('table_number')->nullable()->change();
            
            $table->enum('order_type', ['dine-in', 'takeaway', 'remote-delivery'])
                  ->nullable()
                  ->after('table_number');
                  
            $table->decimal('total_amount', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('table_number')->nullable(false)->change();
            $table->decimal('total_amount', 10, 2)->nullable(false)->change();
            $table->dropColumn('order_type');
        });
    }
};