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
            $table->string('knowledge_area')->nullable()->after('focus_area')->index();
            $table->string('task')->nullable()->after('knowledge_area')->index();
            $table->string('technique')->nullable()->after('task')->index();
            $table->string('perspective')->nullable()->after('technique')->index();
            $table->string('cognitive_level')->nullable()->after('perspective')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'knowledge_area',
                'task',
                'technique',
                'perspective',
                'cognitive_level',
            ]);
        });
    }
};
