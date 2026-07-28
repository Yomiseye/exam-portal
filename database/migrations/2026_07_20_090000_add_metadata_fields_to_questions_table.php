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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('lifecycle')->nullable()->after('difficulty')->index();
            $table->string('domain')->nullable()->after('lifecycle')->index();
            $table->string('focus_area')->nullable()->after('domain')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['lifecycle', 'domain', 'focus_area']);
        });
    }
};
