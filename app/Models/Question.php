<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'question_text', 'image_path', 'question_type', 'explanation', 'explanation_image_path', 'difficulty', 'is_active'])]
class Question extends Model
{
    use HasFactory;

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_MATCHING = 'matching';

    public const TYPES = [
        self::TYPE_SINGLE_CHOICE => 'Single choice',
        self::TYPE_MULTIPLE_CHOICE => 'Multiple correct',
        self::TYPE_TRUE_FALSE => 'True / False',
        self::TYPE_MATCHING => 'Matching',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(QuestionTag::class);
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->question_type] ?? 'Single choice';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? route('question-images.show', ['filename' => basename($this->image_path)])
            : null;
    }

    public function explanationImageUrl(): ?string
    {
        return $this->explanation_image_path
            ? route('question-images.show', ['filename' => basename($this->explanation_image_path)])
            : null;
    }
}
