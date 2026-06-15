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
        Schema::table('exams', function (Blueprint $table): void {
            $table->boolean('allow_pause')->default(false)->after('show_corrections');
        });

        Schema::table('attempts', function (Blueprint $table): void {
            $table->dateTime('paused_at')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table): void {
            $table->dropColumn('paused_at');
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn('allow_pause');
        });
    }
};
