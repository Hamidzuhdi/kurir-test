<?php

use App\Enums\CourierStatus;
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
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->unsignedTinyInteger('level')->comment('Courier level, 1 (lowest) to 5 (highest)');
            $table->string('vehicle_type', 20);
            $table->string('vehicle_plate_number', 15)->nullable();
            $table->string('status', 20)->default(CourierStatus::Active->value);
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('level');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
