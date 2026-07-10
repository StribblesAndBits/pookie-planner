<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jules_days', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('start');
            $table->date('end');
            $table->text('description')->nullable();
            $table->boolean('all_day')->default(true);
            $table->string('recurrence_type')->default('none');
            $table->unsignedInteger('recurrence_interval')->nullable();
            $table->string('recurrence_unit')->nullable();
            $table->json('recurrence_days_of_week')->nullable();
            $table->string('recurrence_end_type')->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->unsignedInteger('recurrence_occurrences')->nullable();
            $table->json('excluded_occurrences')->nullable();
            $table->timestamps();
            $table->index(['start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jules_days');
    }
};
