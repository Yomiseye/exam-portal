<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->string('classification_profile')->default('general')->after('slug')->index();
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('certification_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('certifications')
                ->nullOnDelete();
        });

        $certifications = DB::table('certifications')->get(['id', 'name', 'slug']);
        $categories = DB::table('categories')->whereNull('parent_id')->get(['id', 'name']);

        foreach ($certifications as $certification) {
            $profile = $this->profileFor((string) $certification->name, (string) $certification->slug);

            DB::table('certifications')
                ->where('id', $certification->id)
                ->update(['classification_profile' => $profile]);

            if ($profile === 'general') {
                continue;
            }

            foreach ($categories as $category) {
                if ($this->profileFor((string) $category->name) === $profile) {
                    DB::table('categories')
                        ->where('id', $category->id)
                        ->whereNull('certification_id')
                        ->update(['certification_id' => $certification->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('certification_id');
        });

        Schema::table('certifications', function (Blueprint $table): void {
            $table->dropColumn('classification_profile');
        });
    }

    private function profileFor(string ...$values): string
    {
        $value = Str::lower(implode(' ', $values));

        return match (true) {
            Str::contains($value, ['cbap', 'business analysis']) => 'cbap',
            Str::contains($value, ['itil']) => 'itil',
            Str::contains($value, ['pmi-pmp', 'pmp', 'project management professional', 'project management']) => 'pmp',
            default => 'general',
        };
    }
};
