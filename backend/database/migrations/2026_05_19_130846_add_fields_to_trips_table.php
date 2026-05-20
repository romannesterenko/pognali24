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

            // Удаляем foreign keys
            $table->dropForeign(['from_locality_id']);
            $table->dropForeign(['to_locality_id']);

            // Удаляем колонки
            $table->dropColumn([
                'from_city',
                'to_city',
                'from_locality_id',
                'to_locality_id',
            ]);

            $table->uuid('from_fias_id')
                ->nullable()
                ->after('car_id');

            $table->uuid('to_fias_id')
                ->nullable()
                ->after('from_fias_id');

            $table->foreign('from_fias_id')
                ->references('fias_id')
                ->on('localities')
                ->nullOnDelete();

            $table->foreign('to_fias_id')
                ->references('fias_id')
                ->on('localities')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            //
        });
    }
};
