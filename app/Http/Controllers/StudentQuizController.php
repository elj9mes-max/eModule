<?php

namespace App\Http\Controllers;

use App\Enums\QuizType;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentQuizController extends Controller
{
    /**
     * Submit a student's quiz answers, evaluate server-side, save attempt and return results.
     */
    public function submit(Request $request, Module $module, Quiz $quiz): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['message' => 'Only students can submit quiz attempts.'], 403);
        }

        $validated = $request->validate([
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
            'answers' => ['required', 'array'],
        ]);

        $timeSpentSeconds = (int) ($validated['time_spent_seconds'] ?? 0);
        $rawAnswers = $validated['answers'];

        // Prevent retaking if already completed or pending teacher review
        $existingAttempt = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereIn('status', ['completed', 'pending_review'])
            ->latest('id')
            ->first();

        if ($existingAttempt) {
            $isPending = $existingAttempt->status === 'pending_review';

            return response()->json([
                'success' => false,
                'message' => $isPending
                    ? 'You have already submitted this quiz and your response is awaiting teacher grading.'
                    : 'You have already completed this quiz.',
                'attempt' => [
                    'id' => $existingAttempt->id,
                    'attempt_number' => $existingAttempt->attempt_number,
                    'status' => $existingAttempt->status,
                    'is_pending_review' => $isPending,
                    'score' => $existingAttempt->score,
                    'max_score' => $existingAttempt->max_score,
                    'percentage' => (float) $existingAttempt->percentage,
                    'stars' => $existingAttempt->stars,
                    'is_passed' => $existingAttempt->is_passed,
                    'xp_earned' => $existingAttempt->xp_earned,
                    'time_spent_seconds' => $existingAttempt->time_spent_seconds,
                    'completed_at' => $existingAttempt->completed_at?->toIso8601String(),
                    'teacher_feedback' => $existingAttempt->teacher_feedback,
                ],
            ], 422);
        }

        // Determine attempt number for this student and quiz
        $lastAttemptNumber = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->max('attempt_number') ?? 0;

        $attemptNumber = $lastAttemptNumber + 1;

        // Perform server-side grading and parse answer records
        $evaluation = $this->evaluateQuiz($quiz, $rawAnswers);

        $attempt = DB::transaction(function () use ($user, $quiz, $attemptNumber, $timeSpentSeconds, $evaluation): QuizAttempt {
            $quizAttempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'attempt_number' => $attemptNumber,
                'status' => $evaluation['status'],
                'score' => $evaluation['score'],
                'max_score' => $evaluation['max_score'],
                'percentage' => $evaluation['percentage'],
                'stars' => $evaluation['stars'],
                'is_passed' => $evaluation['is_passed'],
                'xp_earned' => $evaluation['xp_earned'],
                'time_spent_seconds' => $timeSpentSeconds,
                'started_at' => now()->subSeconds(max(1, $timeSpentSeconds)),
                'completed_at' => now(),
            ]);

            foreach ($evaluation['answer_records'] as $record) {
                QuizAttemptAnswer::create([
                    'attempt_id' => $quizAttempt->id,
                    'item_type' => $record['item_type'],
                    'item_id' => $record['item_id'],
                    'answer_data' => $record['answer_data'],
                    'answer_text' => $record['answer_text'] ?? null,
                    'is_correct' => $record['is_correct'],
                    'points_earned' => $record['points_earned'],
                    'points_possible' => $record['points_possible'],
                ]);
            }

            return $quizAttempt;
        });

        if ($request->wantsJson()) {
            $isPending = $attempt->status === 'pending_review';

            return response()->json([
                'success' => true,
                'status' => $attempt->status,
                'is_pending_review' => $isPending,
                'message' => $isPending
                    ? 'Your response has been submitted! Your teacher will review and grade your work.'
                    : 'Quiz completed successfully!',
                'attempt' => [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status,
                    'is_pending_review' => $isPending,
                    'score' => $attempt->score,
                    'max_score' => $attempt->max_score,
                    'percentage' => (float) $attempt->percentage,
                    'stars' => $attempt->stars,
                    'is_passed' => $attempt->is_passed,
                    'xp_earned' => $attempt->xp_earned,
                    'time_spent_seconds' => $attempt->time_spent_seconds,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ],
            ]);
        }

        return back()->with('attempt', $attempt);
    }

    /**
     * Grade the quiz server-side according to the quiz type.
     *
     * @param  array<string, mixed>  $rawAnswers
     * @return array{
     *     status: string,
     *     score: int,
     *     max_score: int,
     *     percentage: float,
     *     stars: int,
     *     is_passed: bool,
     *     xp_earned: int,
     *     answer_records: array<int, array<string, mixed>>
     * }
     */
    private function evaluateQuiz(Quiz $quiz, array $rawAnswers): array
    {
        return match ($quiz->type) {
            QuizType::MultipleChoice => $this->evaluateMultipleChoice($quiz, $rawAnswers),
            QuizType::ColorTheConflict => $this->evaluateColorTheConflict($quiz, $rawAnswers),
            QuizType::TheConflictMap => $this->evaluateConflictMap($quiz, $rawAnswers),
            QuizType::MatchingType => $this->evaluateMatchingType($quiz, $rawAnswers),
            QuizType::Evaluation => $this->evaluateEvaluation($quiz, $rawAnswers),
            QuizType::Reflection => $this->evaluateReflection($quiz, $rawAnswers),
        };
    }

    /**
     * @param  array<string, mixed>  $rawAnswers
     * @return array<string, mixed>
     */
    private function evaluateMultipleChoice(Quiz $quiz, array $rawAnswers): array
    {
        $quiz->loadMissing('questions.options');
        $earnedScore = 0;
        $maxScore = 0;
        $records = [];

        foreach ($quiz->questions as $question) {
            $pts = (int) $question->points;
            $maxScore += $pts;

            $selectedOptionIds = (array) ($rawAnswers[$question->id] ?? []);
            $selectedOptionIds = array_map('intval', array_values($selectedOptionIds));

            $correctOptionIds = $question->options->where('is_correct', true)->pluck('id')->all();
            sort($selectedOptionIds);
            sort($correctOptionIds);

            $isCorrect = count($selectedOptionIds) > 0 && $selectedOptionIds === $correctOptionIds;
            $itemEarned = $isCorrect ? $pts : 0;
            $earnedScore += $itemEarned;

            $records[] = [
                'item_type' => 'multiple_choice',
                'item_id' => $question->id,
                'answer_data' => [
                    'selected_option_ids' => $selectedOptionIds,
                    'selection_type' => $question->selection_type->value,
                ],
                'answer_text' => implode(', ', $selectedOptionIds),
                'is_correct' => $isCorrect,
                'points_earned' => $itemEarned,
                'points_possible' => $pts,
            ];
        }

        return $this->buildSummary($quiz, $earnedScore, $maxScore, $records);
    }

    /**
     * @param  array<string, mixed>  $rawAnswers
     * @return array<string, mixed>
     */
    private function evaluateColorTheConflict(Quiz $quiz, array $rawAnswers): array
    {
        $quiz->loadMissing('conflictCards');
        $earnedScore = 0;
        $maxScore = 0;
        $records = [];

        foreach ($quiz->conflictCards as $card) {
            $pts = (int) $card->points;
            $maxScore += $pts;

            $pickedType = (string) ($rawAnswers[$card->id] ?? '');
            $isCorrect = ! empty($pickedType) && $pickedType === $card->conflict_type->value;
            $itemEarned = $isCorrect ? $pts : 0;
            $earnedScore += $itemEarned;

            $records[] = [
                'item_type' => 'color_conflict',
                'item_id' => $card->id,
                'answer_data' => [
                    'conflict_type' => $pickedType,
                ],
                'answer_text' => $pickedType,
                'is_correct' => $isCorrect,
                'points_earned' => $itemEarned,
                'points_possible' => $pts,
            ];
        }

        return $this->buildSummary($quiz, $earnedScore, $maxScore, $records);
    }

    /**
     * @param  array<string, mixed>  $rawAnswers
     * @return array<string, mixed>
     */
    private function evaluateConflictMap(Quiz $quiz, array $rawAnswers): array
    {
        $quiz->loadMissing('mapEvents');
        $earnedScore = 0;
        $maxScore = 0;
        $records = [];

        foreach ($quiz->mapEvents as $event) {
            $pts = (int) $event->points;
            $maxScore += $pts;

            $assignedType = (string) ($rawAnswers[$event->id] ?? '');
            $isCorrect = ! empty($assignedType) && $assignedType === $event->target_conflict_type->value;
            $itemEarned = $isCorrect ? $pts : 0;
            $earnedScore += $itemEarned;

            $records[] = [
                'item_type' => 'conflict_map',
                'item_id' => $event->id,
                'answer_data' => [
                    'assigned_conflict_type' => $assignedType,
                ],
                'answer_text' => $assignedType,
                'is_correct' => $isCorrect,
                'points_earned' => $itemEarned,
                'points_possible' => $pts,
            ];
        }

        return $this->buildSummary($quiz, $earnedScore, $maxScore, $records);
    }

    /**
     * @param  array<string, mixed>  $rawAnswers
     * @return array<string, mixed>
     */
    private function evaluateMatchingType(Quiz $quiz, array $rawAnswers): array
    {
        $quiz->loadMissing(['matchingItems.correctChoice', 'matchingChoices']);
        $earnedScore = 0;
        $maxScore = 0;
        $records = [];

        foreach ($quiz->matchingItems as $item) {
            $pts = (int) $item->points;
            $maxScore += $pts;

            $pickedLetter = strtoupper((string) ($rawAnswers[$item->id] ?? ''));
            $correctLetter = strtoupper((string) ($item->correctChoice?->choice_letter ?? ''));

            $isCorrect = ! empty($pickedLetter) && $pickedLetter === $correctLetter;
            $itemEarned = $isCorrect ? $pts : 0;
            $earnedScore += $itemEarned;

            $records[] = [
                'item_type' => 'matching',
                'item_id' => $item->id,
                'answer_data' => [
                    'choice_letter' => $pickedLetter,
                ],
                'answer_text' => $pickedLetter,
                'is_correct' => $isCorrect,
                'points_earned' => $itemEarned,
                'points_possible' => $pts,
            ];
        }

        return $this->buildSummary($quiz, $earnedScore, $maxScore, $records);
    }

    /**
     * @param  array<string, mixed>  $rawAnswers
     * @return array<string, mixed>
     */
    private function evaluateEvaluation(Quiz $quiz, array $rawAnswers): array
    {
        $quiz->loadMissing('evaluationItems.options');
        $maxScore = 0;
        $records = [];

        foreach ($quiz->evaluationItems as $item) {
            $pts = (int) $item->points;
            $maxScore += $pts;

            $submission = (array) ($rawAnswers[$item->id] ?? $rawAnswers[(string) $item->id] ?? []);
            $optionId = isset($submission['option_id']) ? (int) $submission['option_id'] : null;
            $blanks = isset($submission['blanks']) ? (array) $submission['blanks'] : [];

            $selectedOption = $item->options->firstWhere('id', $optionId);
            $stance = $selectedOption?->stance_value
                ?: ($selectedOption?->option_text ?: ($submission['stance'] ?? null));

            $records[] = [
                'item_type' => 'evaluation',
                'item_id' => $item->id,
                'answer_data' => [
                    'option_id' => $optionId,
                    'stance' => $stance,
                    'blanks' => $blanks,
                ],
                'answer_text' => implode(' | ', $blanks),
                'is_correct' => null, // Pending teacher grading
                'points_earned' => 0, // Pending teacher grading
                'points_possible' => $pts,
            ];
        }

        return [
            'status' => 'pending_review',
            'score' => 0,
            'max_score' => max(1, $maxScore),
            'percentage' => 0.00,
            'stars' => 0,
            'is_passed' => false,
            'xp_earned' => 0,
            'answer_records' => $records,
        ];
    }

    /**
     * @param  array<string, mixed>  $rawAnswers
     * @return array<string, mixed>
     */
    private function evaluateReflection(Quiz $quiz, array $rawAnswers): array
    {
        $quiz->loadMissing('reflectionPrompts');
        $maxScore = 0;
        $records = [];

        foreach ($quiz->reflectionPrompts as $prompt) {
            $pts = (int) $prompt->points;
            $maxScore += $pts;

            $text = trim((string) ($rawAnswers[$prompt->id] ?? ''));
            $wordCount = ! empty($text) ? str_word_count($text) : 0;

            $records[] = [
                'item_type' => 'reflection',
                'item_id' => $prompt->id,
                'answer_data' => [
                    'text' => $text,
                    'word_count' => $wordCount,
                ],
                'answer_text' => $text,
                'is_correct' => null, // Pending teacher grading
                'points_earned' => 0, // Pending teacher grading
                'points_possible' => $pts,
            ];
        }

        return [
            'status' => 'pending_review',
            'score' => 0,
            'max_score' => max(1, $maxScore),
            'percentage' => 0.00,
            'stars' => 0,
            'is_passed' => false,
            'xp_earned' => 0,
            'answer_records' => $records,
        ];
    }

    /**
     * Calculate summary percentages, stars, and XP.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{
     *     status: string,
     *     score: int,
     *     max_score: int,
     *     percentage: float,
     *     stars: int,
     *     is_passed: bool,
     *     xp_earned: int,
     *     answer_records: array<int, array<string, mixed>>
     * }
     */
    private function buildSummary(Quiz $quiz, int $earnedScore, int $maxScore, array $records, string $status = 'completed'): array
    {
        $safeMax = max(1, $maxScore);
        $percentage = round(($earnedScore / $safeMax) * 100, 2);
        $passThreshold = (float) ($quiz->passing_percentage ?? 70);
        $isPassed = $percentage >= $passThreshold;

        $stars = 0;
        if ($percentage >= 90) {
            $stars = 3;
        } elseif ($percentage >= $passThreshold) {
            $stars = 2;
        } elseif ($percentage >= 40) {
            $stars = 1;
        }

        $baseXp = (int) ($quiz->xp_reward ?? 100);
        $xpEarned = (int) round(($percentage / 100) * $baseXp);

        return [
            'status' => $status,
            'score' => $earnedScore,
            'max_score' => $safeMax,
            'percentage' => $percentage,
            'stars' => $stars,
            'is_passed' => $isPassed,
            'xp_earned' => $xpEarned,
            'answer_records' => $records,
        ];
    }
}
