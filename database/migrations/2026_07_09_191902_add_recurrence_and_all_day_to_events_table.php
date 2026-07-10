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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('all_day')->default(false)->after('description');
            $table->string('recurrence_type')->default('none')->after('all_day');
            $table->unsignedInteger('recurrence_interval')->nullable()->after('recurrence_type');
            $table->string('recurrence_unit')->nullable()->after('recurrence_interval');
            $table->json('recurrence_days_of_week')->nullable()->after('recurrence_unit');
            $table->string('recurrence_end_type')->nullable()->after('recurrence_days_of_week');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_end_type');
            $table->unsignedInteger('recurrence_occurrences')->nullable()->after('recurrence_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'all_day',
                'recurrence_type',
                'recurrence_interval',
                'recurrence_unit',
                'recurrence_days_of_week',
                'recurrence_end_type',
                'recurrence_end_date',
                'recurrence_occurrences',
            ]);
        });
    }
};
