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
            $table->string('question_type')->default('single_choice')->after('question_text')->index();
        });

        Schema::table('options', function (Blueprint $table) {
            $table->text('match_text')->nullable()->after('option_text');
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->json('selected_option_ids')->nullable()->after('selected_option_id');
            $table->json('matching_answer')->nullable()->after('selected_option_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropColumn(['selected_option_ids', 'matching_answer']);
        });

        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('match_text');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
