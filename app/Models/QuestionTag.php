<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class QuestionTag extends Model
{
    use HasFactory;

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}
