<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jules_days', function (Blueprint $table): void {
            $table->string('transition_time', 5)->nullable()->after('end');
        });
    }

    public function down(): void
    {
        Schema::table('jules_days', function (Blueprint $table): void {
            $table->dropColumn('transition_time');
        });
    }
};
