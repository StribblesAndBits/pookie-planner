<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilities', function (Blueprint $table) {
            $table->string('utility_currency')->default('dollars')->after('amount');
        });

        DB::table('utilities')->whereNull('utility_currency')->update(['utility_currency' => 'dollars']);
    }

    public function down(): void
    {
        Schema::table('utilities', function (Blueprint $table) {
            $table->dropColumn('utility_currency');
        });
    }
};
