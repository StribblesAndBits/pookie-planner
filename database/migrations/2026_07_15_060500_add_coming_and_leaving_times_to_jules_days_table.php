<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jules_days', function (Blueprint $table): void {
            $table->string('coming_time', 5)->nullable()->after('transition_time');
            $table->string('leaving_time', 5)->nullable()->after('coming_time');
        });

        DB::table('jules_days')
            ->whereNull('coming_time')
            ->whereNotNull('transition_time')
            ->update(['coming_time' => DB::raw('transition_time')]);

        DB::table('jules_days')
            ->whereNull('leaving_time')
            ->whereNotNull('transition_time')
            ->update(['leaving_time' => DB::raw('transition_time')]);
    }

    public function down(): void
    {
        Schema::table('jules_days', function (Blueprint $table): void {
            $table->dropColumn(['coming_time', 'leaving_time']);
        });
    }
};
