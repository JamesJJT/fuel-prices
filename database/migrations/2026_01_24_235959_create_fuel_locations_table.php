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
        Schema::create('fuel_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source')->index();
            $table->string('provider_site_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->timestamps();

            $table->unique(['source', 'provider_site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_locations');
    }
};
