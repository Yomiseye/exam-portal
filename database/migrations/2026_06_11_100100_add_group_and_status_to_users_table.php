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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('student_group_id')
                ->nullable()
                ->after('role')
                ->constrained('student_groups')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('student_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_group_id');
            $table->dropColumn('is_active');
        });
    }
};
