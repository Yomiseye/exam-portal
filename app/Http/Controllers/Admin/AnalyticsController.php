<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Show administrative reporting and analytics.
     */
    public function __invoke(Request $request): View
    {
        [$filters, $attempts] = $this->filteredAttempts($request);

        $answers = $attempts
            ->flatMap(fn (Attempt $attempt) => $attempt->answers);

        $exams = Exam::query()
            ->orderBy('title')
            ->get();

        return view('admin.analytics.index', [
            'attempts' => $attempts,
            'exams' => $exams,
            'filters' => $filters,
            'summary' => $this->summary($attempts),
            'statusRows' => $this->statusRows($attempts),
            'examRows' => $this->examRows($attempts),
            'groupRows' => $this->groupRows($attempts),
            'studentRows' => $this->studentRows($attempts),
            'metadataSections' => $this->metadataSections($answers),
            'missedQuestions' => $this->missedQuestions($answers),
        ]);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        abort_unless(in_array($report, ['summary', 'students', 'missed-questions'], true), 404);

        [, $attempts] = $this->filteredAttempts($request);
        $answers = $attempts->flatMap(fn (Attempt $attempt) => $attempt->answers);
        $filename = 'testportal-'.$report.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report, $attempts, $answers): void {
            $handle = fopen('php://output', 'w');

            if ($report === 'summary') {
                $summary = $this->summary($attempts);
                fputcsv($handle, ['Metric', 'Value']);
                fputcsv($handle, ['Submitted Attempts', $summary['attempts']]);
                fputcsv($handle, ['Passed', $summary['passed']]);
                fputcsv($handle, ['Failed', $summary['failed']]);
                fputcsv($handle, ['Pass Rate', $summary['pass_rate'].'%']);
                fputcsv($handle, ['Average Score', $summary['average_score'].'%']);
                fputcsv($handle, []);
                fputcsv($handle, ['Exam', 'Attempts', 'Average', 'Pass Rate']);

                foreach ($this->examRows($attempts) as $row) {
                    fputcsv($handle, [$row['label'], $row['attempts'], $row['average'].'%', $row['pass_rate'].'%']);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['Group', 'Attempts', 'Average', 'Pass Rate']);

                foreach ($this->groupRows($attempts) as $row) {
                    fputcsv($handle, [$row['label'], $row['attempts'], $row['average'].'%', $row['pass_rate'].'%']);
                }
            }

            if ($report === 'students') {
                fputcsv($handle, ['Student', 'Email', 'Group', 'Attempts', 'Average Score', 'Best Score', 'Latest Score', 'Pass Rate', 'Latest Attempt Date']);

                foreach ($this->studentRows($attempts) as $row) {
                    fputcsv($handle, [
                        $row['student'],
                        $row['email'],
                        $row['group'],
                        $row['attempts'],
                        $row['average'].'%',
                        $row['best'].'%',
                        $row['latest_score'].'%',
                        $row['pass_rate'].'%',
                        $row['latest_attempt_date'],
                    ]);
                }
            }

            if ($report === 'missed-questions') {
                fputcsv($handle, ['Question ID', 'Question', 'Missed', 'Attempts', 'Miss Rate']);

                foreach ($this->missedQuestions($answers) as $row) {
                    fputcsv($handle, [
                        $row['question_id'],
                        $row['question'],
                        $row['missed'],
                        $row['attempts'],
                        $row['miss_rate'].'%',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: Collection<int, Attempt>}
     */
    private function filteredAttempts(Request $request): array
    {
        $filters = $request->validate([
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $attempts = Attempt::query()
            ->with([
                'exam',
                'user.studentGroup',
                'answers.question.options',
            ])
            ->whereIn('status', ['passed', 'failed'])
            ->when($filters['exam_id'] ?? null, fn ($query, $examId) => $query->where('exam_id', $examId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '<=', $date))
            ->latest('submitted_at')
            ->get();

        return [$filters, $attempts];
    }

    /**
     * @param  Collection<int, Attempt>  $attempts
     * @return array<string, int|float>
     */
    private function summary(Collection $attempts): array
    {
        $total = $attempts->count();
        $passed = $attempts->where('status', 'passed')->count();
        $failed = $attempts->where('status', 'failed')->count();
        $average = $total > 0 ? round($attempts->avg('percentage'), 1) : 0;

        return [
            'attempts' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'pass_rate' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
            'average_score' => $average,
        ];
    }

    /**
     * @param  Collection<int, Attempt>  $attempts
     * @return array<int, array{label: string, count: int, percentage: int}>
     */
    private function statusRows(Collection $attempts): array
    {
        $total = max($attempts->count(), 1);

        return collect(['passed' => 'Passed', 'failed' => 'Failed'])
            ->map(fn (string $label, string $status) => [
                'label' => $label,
                'count' => $attempts->where('status', $status)->count(),
                'percentage' => (int) round(($attempts->where('status', $status)->count() / $total) * 100),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Attempt>  $attempts
     * @return array<int, array{label: string, attempts: int, average: float|int, pass_rate: int}>
     */
    private function examRows(Collection $attempts): array
    {
        return $attempts
            ->groupBy('exam_id')
            ->map(function (Collection $examAttempts): array {
                $total = $examAttempts->count();
                $passed = $examAttempts->where('status', 'passed')->count();

                return [
                    'label' => $examAttempts->first()->exam?->title ?? 'Unknown exam',
                    'attempts' => $total,
                    'average' => $total > 0 ? round($examAttempts->avg('percentage'), 1) : 0,
                    'pass_rate' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Attempt>  $attempts
     * @return array<int, array{label: string, attempts: int, average: float|int, pass_rate: int}>
     */
    private function groupRows(Collection $attempts): array
    {
        return $attempts
            ->groupBy(fn (Attempt $attempt) => $attempt->user?->studentGroup?->name ?? 'No group')
            ->map(function (Collection $groupAttempts, string $label): array {
                $total = $groupAttempts->count();
                $passed = $groupAttempts->where('status', 'passed')->count();

                return [
                    'label' => $label,
                    'attempts' => $total,
                    'average' => $total > 0 ? round($groupAttempts->avg('percentage'), 1) : 0,
                    'pass_rate' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Attempt>  $attempts
     * @return array<int, array{student: string, email: string, group: string, attempts: int, average: float|int, best: int, latest_score: int, pass_rate: int, latest_attempt_date: string}>
     */
    private function studentRows(Collection $attempts): array
    {
        return $attempts
            ->groupBy('user_id')
            ->map(function (Collection $studentAttempts): array {
                $total = $studentAttempts->count();
                $passed = $studentAttempts->where('status', 'passed')->count();
                $latestAttempt = $studentAttempts->sortByDesc('submitted_at')->first();
                $student = $latestAttempt?->user;

                return [
                    'student' => $student?->name ?? 'Unknown student',
                    'email' => $student?->email ?? '',
                    'group' => $student?->studentGroup?->name ?? 'No group',
                    'attempts' => $total,
                    'average' => $total > 0 ? round($studentAttempts->avg('percentage'), 1) : 0,
                    'best' => (int) ($studentAttempts->max('percentage') ?? 0),
                    'latest_score' => (int) ($latestAttempt?->percentage ?? 0),
                    'pass_rate' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
                    'latest_attempt_date' => $latestAttempt?->submitted_at?->format('M j, Y g:i A') ?? '',
                ];
            })
            ->sortByDesc('latest_attempt_date')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AttemptAnswer>  $answers
     * @return array<string, array{title: string, rows: array<int, array{label: string, correct: int, total: int, percentage: int, status: string}>}>
     */
    private function metadataSections(Collection $answers): array
    {
        return [
            'lifecycle' => [
                'title' => 'Lifecycle',
                'rows' => $this->answerRows($answers, fn (AttemptAnswer $answer) => [
                    $answer->question->lifecycle ?? 'unclassified',
                    $answer->question->lifecycleLabel(),
                ]),
            ],
            'eco_domain' => [
                'title' => 'Eco Domain',
                'rows' => $this->answerRows($answers, fn (AttemptAnswer $answer) => [
                    $answer->question->eco_domain ?? 'unclassified',
                    $answer->question->ecoDomainLabel(),
                ]),
            ],
            'performance_domain' => [
                'title' => 'Performance Domain',
                'rows' => $this->answerRows($answers, fn (AttemptAnswer $answer) => [
                    $answer->question->domain ?? 'unclassified',
                    $answer->question->performanceDomainLabel(),
                ]),
            ],
            'focus_area' => [
                'title' => 'Focus Area',
                'rows' => $this->answerRows($answers, fn (AttemptAnswer $answer) => [
                    $answer->question->focus_area ?? 'unclassified',
                    $answer->question->focusAreaLabel(),
                ]),
            ],
        ];
    }

    /**
     * @param  Collection<int, AttemptAnswer>  $answers
     * @param  callable(AttemptAnswer): array{0: string, 1: string}  $classifier
     * @return array<int, array{label: string, correct: int, total: int, percentage: int, status: string}>
     */
    private function answerRows(Collection $answers, callable $classifier): array
    {
        return $answers
            ->groupBy(fn (AttemptAnswer $answer) => $classifier($answer)[0])
            ->map(function (Collection $groupedAnswers) use ($classifier): array {
                $firstAnswer = $groupedAnswers->first();
                $total = $groupedAnswers->count();
                $correct = $groupedAnswers->where('is_correct', true)->count();
                $percentage = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

                return [
                    'label' => $classifier($firstAnswer)[1],
                    'correct' => $correct,
                    'total' => $total,
                    'percentage' => $percentage,
                    'status' => $this->performanceStatus($percentage),
                ];
            })
            ->sortBy('percentage')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AttemptAnswer>  $answers
     * @return array<int, array{question_id: int, question: string, exam: string, missed: int, attempts: int, miss_rate: int}>
     */
    private function missedQuestions(Collection $answers): array
    {
        return $answers
            ->groupBy('question_id')
            ->map(function (Collection $questionAnswers): array {
                $firstAnswer = $questionAnswers->first();
                $attempts = $questionAnswers->count();
                $missed = $questionAnswers->where('is_correct', false)->count();

                return [
                    'question_id' => (int) $firstAnswer->question_id,
                    'question' => str(strip_tags($firstAnswer->question?->question_text ?? ''))
                        ->squish()
                        ->limit(140)
                        ->toString(),
                    'exam' => 'Filtered attempts',
                    'missed' => $missed,
                    'attempts' => $attempts,
                    'miss_rate' => $attempts > 0 ? (int) round(($missed / $attempts) * 100) : 0,
                ];
            })
            ->filter(fn (array $row) => $row['missed'] > 0)
            ->sortByDesc('miss_rate')
            ->take(10)
            ->values()
            ->all();
    }

    private function performanceStatus(int $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Strength',
            $percentage >= 50 => 'Developing',
            default => 'Knowledge Gap',
        };
    }
}
