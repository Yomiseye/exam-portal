<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'duration_minutes',
    'total_questions',
    'pass_mark',
    'is_randomized',
    'show_corrections',
    'allow_pause',
    'is_active',
])]
class Exam extends Model
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
            'duration_minutes' => 'integer',
            'total_questions' => 'integer',
            'pass_mark' => 'integer',
            'is_randomized' => 'boolean',
            'show_corrections' => 'boolean',
            'allow_pause' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'exam_categories')
            ->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function groupAssignments(): HasMany
    {
        return $this->hasMany(GroupExamAssignment::class);
    }

    public function retakePermissions(): HasMany
    {
        return $this->hasMany(ExamRetakePermission::class);
    }
}
