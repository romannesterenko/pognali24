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
        Schema::table('trips', function (Blueprint $table) {

            $table->foreignId('from_locality_id')
                ->nullable()
                ->after('car_id')
                ->constrained('localities')
                ->nullOnDelete();

            $table->foreignId('to_locality_id')
                ->nullable()
                ->after('from_locality_id')
                ->constrained('localities')
                ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
