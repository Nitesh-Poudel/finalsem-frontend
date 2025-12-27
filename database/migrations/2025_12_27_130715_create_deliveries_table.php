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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            // Nepali Address System Fields (all nullable)
            $table->string('ward_number')->nullable();       
            $table->string('tole')->nullable();             
            $table->string('local_government')->nullable();  // स्थानीय तह (न.पा./गा.पा.)
            $table->string('municipality')->nullable();      // नगरपालिका
            $table->string('district')->nullable();          // जिल्ला
            $table->string('province')->nullable();          // प्रदेश
            
            // Location Coordinates (for GPS)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            
            // Contact Information
            $table->string('phone')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            
            // Flags
            $table->boolean('is_current_location')->default(false);
            $table->text('landmark')->nullable();             
            // Delivery Tracking
            $table->enum('status', [
                'pending',      
                'assigned',     
                'picked',       
                'on_the_way',  
                'delivered',    
                'cancelled'   
            ])->default('pending');
            
            $table->string('tracking_code')->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable()->default(100);
            
            // Timestamps
            $table->timestamp('estimated_delivery_time')->nullable();
            $table->timestamp('actual_delivery_time')->nullable();
            
            // Additional Notes
            $table->text('delivery_notes')->nullable();
            $table->text('customer_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('order_id');
            $table->index('status');
            $table->index('district');
            $table->index('phone');
            $table->index('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};