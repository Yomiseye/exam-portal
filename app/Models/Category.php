<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'certification_id', 'name', 'description', 'is_active'])]
class Category extends Model
{
    use HasFactory;

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

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function fullName(): string
    {
        return $this->parent ? "{$this->parent->name} / {$this->name}" : $this->name;
    }

    public function classificationProfile(): string
    {
        $category = $this;

        while ($category) {
            $category->loadMissing('certification', 'parent');

            if ($category->certification) {
                return $category->certification->classification_profile;
            }

            $category = $category->parent;
        }

        return Certification::PROFILE_GENERAL;
    }

    public function certificationName(): ?string
    {
        $category = $this;

        while ($category) {
            $category->loadMissing('certification', 'parent');

            if ($category->certification) {
                return $category->certification->name;
            }

            $category = $category->parent;
        }

        return null;
    }

    public function certificationId(): ?int
    {
        $category = $this;

        while ($category) {
            $category->loadMissing('certification', 'parent');

            if ($category->certification) {
                return $category->certification->id;
            }

            $category = $category->parent;
        }

        return null;
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_categories')
            ->withTimestamps();
    }
}
