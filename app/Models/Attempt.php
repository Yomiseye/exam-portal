<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'exam_id',
    'score',
    'total_questions',
    'percentage',
    'status',
    'started_at',
    'expires_at',
    'paused_at',
    'current_question_index',
    'submitted_at',
])]
class Attempt extends Model
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
            'score' => 'integer',
            'total_questions' => 'integer',
            'percentage' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'paused_at' => 'datetime',
            'current_question_index' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->paused_at === null && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    public function pause(): void
    {
        if (! $this->isPaused()) {
            $this->update(['paused_at' => now()]);
        }
    }

    public function resume(): void
    {
        if (! $this->paused_at) {
            return;
        }

        $pausedSeconds = $this->paused_at->diffInSeconds(now());

        $this->update([
            'expires_at' => $this->expires_at?->copy()->addSeconds($pausedSeconds),
            'paused_at' => null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
