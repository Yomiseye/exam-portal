<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'classification_profile', 'description', 'image_path', 'is_active'])]
class Certification extends Model
{
    use HasFactory;

    public const PROFILE_GENERAL = 'general';

    public const PROFILE_PMP = 'pmp';

    public const PROFILE_CBAP = 'cbap';

    public const PROFILE_ITIL = 'itil';

    public const CLASSIFICATION_PROFILES = [
        self::PROFILE_GENERAL => 'General',
        self::PROFILE_PMP => 'PMP',
        self::PROFILE_CBAP => 'CBAP',
        self::PROFILE_ITIL => 'ITIL',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function packages(): HasMany
    {
        return $this->hasMany(CertificationPackage::class)->orderBy('sort_order')->orderBy('name');
    }

    public function activePackages(): HasMany
    {
        return $this->packages()->where('is_active', true);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function classificationProfileLabel(): string
    {
        return self::CLASSIFICATION_PROFILES[$this->classification_profile] ?? 'General';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
