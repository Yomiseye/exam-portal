<?php

use App\Models\Certification;
use App\Models\CertificationPackage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('commercial:seed-pmp-packages {certification=Project Management Professional} {--activate : Create packages as active instead of inactive drafts}', function () {
    $certificationName = trim((string) $this->argument('certification'));
    $certificationSlug = Str::slug($certificationName);
    $certification = Certification::query()
        ->where('name', $certificationName)
        ->orWhere('slug', $certificationSlug)
        ->orWhereRaw('LOWER(name) = ?', [Str::lower($certificationName)])
        ->first();

    if (! $certification) {
        $this->error("Certification not found: {$certificationName}");
        $this->line('Available certifications:');

        Certification::query()
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->each(fn (Certification $certification) => $this->line("- {$certification->name} ({$certification->slug})"));

        $this->line('Rerun the command with the exact certification name shown above, or use the slug.');

        return 1;
    }

    $isActive = (bool) $this->option('activate');
    $packages = [
        [
            'name' => 'Practice Essentials',
            'description' => 'For candidates who want focused question practice and a lower entry point into PMP preparation. Includes practice questions, topic-based quizzes, basic mock exams, and result review.',
            'duration_days' => 30,
            'badge' => 'Starter',
            'sort_order' => 1,
        ],
        [
            'name' => 'Exam Readiness',
            'description' => 'For candidates actively preparing to sit the PMP exam soon. Includes Practice Essentials access, more mock exams, timed exam simulations, detailed result review, corrections, and explanations.',
            'duration_days' => 60,
            'badge' => 'Recommended',
            'sort_order' => 2,
        ],
        [
            'name' => 'Certification Success Bundle',
            'description' => 'For candidates who want the most complete preparation access and maximum confidence before the PMP exam. Includes Exam Readiness access, full mock exam bank, simulations, retry practice support, full explanations, and future priority support.',
            'duration_days' => 90,
            'badge' => 'Best Value',
            'sort_order' => 3,
        ],
    ];

    foreach ($packages as $packageData) {
        $slug = Str::slug($packageData['name']);
        $package = CertificationPackage::query()->updateOrCreate(
            [
                'certification_id' => $certification->id,
                'slug' => $slug,
            ],
            [
                'name' => $packageData['name'],
                'description' => $packageData['description'],
                'price' => 0,
                'duration_days' => $packageData['duration_days'],
                'badge' => $packageData['badge'],
                'sort_order' => $packageData['sort_order'],
                'is_active' => $isActive,
            ],
        );

        $this->line(($package->wasRecentlyCreated ? 'Created' : 'Updated').": {$package->name}");
    }

    $status = $isActive ? 'active' : 'inactive draft';
    $this->info("PMP package presets are ready as {$status} packages.");
    $this->line('Edit prices, assign exams, and activate packages from Admin > Certifications before public launch.');

    return 0;
})->purpose('Create the three Project Management Professional commercial package presets');
