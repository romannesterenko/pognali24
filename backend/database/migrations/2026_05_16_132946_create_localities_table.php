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
        Schema::create('localities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('region_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->string('type')->nullable();

            $table->string('full_name');

            $table->string('slug')->nullable()->index();

            $table->string('fias_id')->unique();

            $table->string('kladr_id')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->integer('population')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localities');
    }
};
