<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Since we are using SQLite, and we must not install any packages as much as possible in this test..,
     * We are going to recreate `plans` table to make the monthly_cost into a decimal that store cents
     */
    public function up(): void
    {
        Schema::dropIfExists('plans');

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('monthly_cost', 15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('monthly_cost');
        });
    }
};
