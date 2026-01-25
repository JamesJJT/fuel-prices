<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fuel_price_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('fuel_type')->index();
            $table->decimal('price', 10, 4)->nullable();
            $table->string('currency', 8)->default('GBP');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('fuel_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_price_history');
    }
};
