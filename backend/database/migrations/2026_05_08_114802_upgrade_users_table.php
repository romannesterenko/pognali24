<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('phone')->nullable()->after('email');

            $table->string('avatar')->nullable();

            $table->date('birth_date')->nullable();

            $table->string('gender')->nullable();

            $table->decimal('rating', 3, 2)->default(5);

            $table->unsignedInteger('reviews_count')->default(0);

            $table->boolean('is_verified')->default(false);

            $table->timestamp('phone_verified_at')->nullable();

            $table->timestamp('last_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        //
    }
};
