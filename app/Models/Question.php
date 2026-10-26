<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'question_text', 'image_path', 'question_type', 'explanation', 'explanation_image_path', 'difficulty', 'lifecycle', 'eco_domain', 'domain', 'focus_area', 'knowledge_area', 'task', 'technique', 'perspective', 'cognitive_level', 'is_active'])]
class Question extends Model
{
    use HasFactory;

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_MATCHING = 'matching';

    public const TYPE_DRAG_DROP = 'drag_drop';

    public const TYPES = [
        self::TYPE_SINGLE_CHOICE => 'Single choice',
        self::TYPE_MULTIPLE_CHOICE => 'Multiple correct',
        self::TYPE_TRUE_FALSE => 'True / False',
        self::TYPE_MATCHING => 'Matching',
        self::TYPE_DRAG_DROP => 'Drag and Drop',
    ];

    public const LIFECYCLES = [
        'predictive' => 'Predictive',
        'agile' => 'Agile',
        'hybrid' => 'Hybrid',
    ];

    public const ECO_DOMAINS = [
        'people' => 'People',
        'process' => 'Process',
        'environment' => 'Environment',
    ];

    public const PERFORMANCE_DOMAINS = [
        'scope' => 'Scope',
        'schedule' => 'Schedule',
        'resource' => 'Resource',
        'finance' => 'Finance',
        'risk' => 'Risk',
        'stakeholder' => 'Stakeholder',
        'governance' => 'Governance',
    ];

    public const DOMAINS = self::PERFORMANCE_DOMAINS;

    public const FOCUS_AREAS = [
        'initiating' => 'Initiating',
        'planning' => 'Planning',
        'executing' => 'Executing',
        'monitoring_controlling' => 'Monitoring & Controlling',
        'closing' => 'Closing',
    ];

    public const CBAP_KNOWLEDGE_AREAS = [
        'business_analysis_planning_and_monitoring' => 'Business Analysis Planning and Monitoring',
        'elicitation_and_collaboration' => 'Elicitation and Collaboration',
        'requirements_life_cycle_management' => 'Requirements Life Cycle Management',
        'strategy_analysis' => 'Strategy Analysis',
        'requirements_analysis_and_design_definition' => 'Requirements Analysis and Design Definition',
        'solution_evaluation' => 'Solution Evaluation',
    ];

    public const CBAP_PERSPECTIVES = [
        'agile' => 'Agile',
        'business_intelligence' => 'Business Intelligence',
        'business_architecture' => 'Business Architecture',
        'business_process_management' => 'Business Process Management',
        'information_technology' => 'Information Technology',
        'project_management' => 'Project Management',
    ];

    public const COGNITIVE_LEVELS = [
        'recall' => 'Recall',
        'understand' => 'Understand',
        'apply' => 'Apply',
        'analyze' => 'Analyze',
        'evaluate' => 'Evaluate',
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

    public function lifecycleLabel(): string
    {
        return self::LIFECYCLES[$this->lifecycle] ?? 'Unclassified';
    }

    public function domainLabel(): string
    {
        return $this->performanceDomainLabel();
    }

    public function ecoDomainLabel(): string
    {
        return self::ECO_DOMAINS[$this->eco_domain] ?? 'Unclassified';
    }

    public function performanceDomainLabel(): string
    {
        return self::PERFORMANCE_DOMAINS[$this->domain] ?? 'Unclassified';
    }

    public function focusAreaLabel(): string
    {
        return self::FOCUS_AREAS[$this->focus_area] ?? 'Unclassified';
    }

    public function knowledgeAreaLabel(): string
    {
        return self::CBAP_KNOWLEDGE_AREAS[$this->knowledge_area] ?? 'Unclassified';
    }

    public function perspectiveLabel(): string
    {
        return self::CBAP_PERSPECTIVES[$this->perspective] ?? 'Unclassified';
    }

    public function cognitiveLevelLabel(): string
    {
        return self::COGNITIVE_LEVELS[$this->cognitive_level] ?? 'Unclassified';
    }

    public function classificationProfile(): string
    {
        return $this->category?->classificationProfile() ?? Certification::PROFILE_GENERAL;
    }

    public function certificationName(): ?string
    {
        return $this->category?->certificationName();
    }

    public function certificationId(): ?int
    {
        return $this->category?->certificationId();
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
