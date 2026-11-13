<?php

namespace App\Services;

use App\Enums\ConflictType;
use App\Models\ColorConflictCard;
use App\Models\ConflictMapEvent;
use App\Models\ConflictMapZone;
use App\Models\EvaluationItem;
use App\Models\EvaluationItemOption;
use App\Models\MatchingChoice;
use App\Models\MatchingItem;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\ReflectionPrompt;
use Illuminate\Support\Collection;

class QuizAnswerContextBuilder
{
    /**
     * Build lookup dictionaries from relations already loaded on a single Quiz.
     *
     * @return array{
     *     evaluationItems: Collection<int, EvaluationItem>,
     *     reflectionPrompts: Collection<int, ReflectionPrompt>,
     *     mcqQuestions: Collection<int, QuizQuestion>,
     *     conflictCards: Collection<int, ColorConflictCard>,
     *     mapEvents: Collection<int, ConflictMapEvent>,
     *     mapZonesByQuiz: Collection<int, Collection<int, ConflictMapZone>>,
     *     matchingItems: Collection<int, MatchingItem>,
     *     matchingChoicesByQuiz: Collection<int, Collection<int, MatchingChoice>>
     * }
     */
    public function dictionariesFromQuiz(Quiz $quiz): array
    {
        return [
            'evaluationItems' => $quiz->relationLoaded('evaluationItems') ? $quiz->evaluationItems->keyBy('id') : collect(),
            'reflectionPrompts' => $quiz->relationLoaded('reflectionPrompts') ? $quiz->reflectionPrompts->keyBy('id') : collect(),
            'mcqQuestions' => $quiz->relationLoaded('questions') ? $quiz->questions->keyBy('id') : collect(),
            'conflictCards' => $quiz->relationLoaded('conflictCards') ? $quiz->conflictCards->keyBy('id') : collect(),
            'mapEvents' => $quiz->relationLoaded('mapEvents') ? $quiz->mapEvents->keyBy('id') : collect(),
            'mapZonesByQuiz' => $quiz->relationLoaded('mapZones') ? collect([$quiz->id => $quiz->mapZones]) : collect(),
            'matchingItems' => $quiz->relationLoaded('matchingItems') ? $quiz->matchingItems->keyBy('id') : collect(),
            'matchingChoicesByQuiz' => $quiz->relationLoaded('matchingChoices') ? collect([$quiz->id => $quiz->matchingChoices]) : collect(),
        ];
    }

    /**
     * Batch-load lookup dictionaries for a collection of QuizAttempt models without N+1 queries.
     *
     * @param  Collection<int, QuizAttempt>  $attempts
     * @return array{
     *     evaluationItems: Collection<int, EvaluationItem>,
     *     reflectionPrompts: Collection<int, ReflectionPrompt>,
     *     mcqQuestions: Collection<int, QuizQuestion>,
     *     conflictCards: Collection<int, ColorConflictCard>,
     *     mapEvents: Collection<int, ConflictMapEvent>,
     *     mapZonesByQuiz: Collection<int, Collection<int, ConflictMapZone>>,
     *     matchingItems: Collection<int, MatchingItem>,
     *     matchingChoicesByQuiz: Collection<int, Collection<int, MatchingChoice>>
     * }
     */
    public function dictionariesFromAttempts(Collection $attempts): array
    {
        $allAnswers = $attempts->flatMap->answers;

        $idsFor = static fn (string $type): Collection => $allAnswers
            ->where('item_type', $type)
            ->pluck('item_id')
            ->filter()
            ->unique()
            ->values();

        $evalIds = $idsFor('evaluation');
        $evaluationItems = $evalIds->isEmpty()
            ? collect()
            : EvaluationItem::query()->with('options')->whereIn('id', $evalIds)->get()->keyBy('id');

        $refIds = $idsFor('reflection');
        $reflectionPrompts = $refIds->isEmpty()
            ? collect()
            : ReflectionPrompt::query()->whereIn('id', $refIds)->get()->keyBy('id');

        $mcqIds = $idsFor('multiple_choice');
        $mcqQuestions = $mcqIds->isEmpty()
            ? collect()
            : QuizQuestion::query()->with('options')->whereIn('id', $mcqIds)->get()->keyBy('id');

        $colorIds = $idsFor('color_conflict');
        $conflictCards = $colorIds->isEmpty()
            ? collect()
            : ColorConflictCard::query()->whereIn('id', $colorIds)->get()->keyBy('id');

        $mapIds = $idsFor('conflict_map');
        $mapEvents = $mapIds->isEmpty()
            ? collect()
            : ConflictMapEvent::query()->whereIn('id', $mapIds)->get()->keyBy('id');

        $mapQuizIds = $mapEvents->pluck('quiz_id')->filter()->unique()->values();
        $mapZonesByQuiz = $mapQuizIds->isEmpty()
            ? collect()
            : ConflictMapZone::query()
                ->whereIn('quiz_id', $mapQuizIds)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('quiz_id');

        $matchingIds = $idsFor('matching');
        $matchingItems = $matchingIds->isEmpty()
            ? collect()
            : MatchingItem::query()->with('correctChoice')->whereIn('id', $matchingIds)->get()->keyBy('id');

        $matchingQuizIds = $matchingItems->pluck('quiz_id')->filter()->unique()->values();
        $matchingChoicesByQuiz = $matchingQuizIds->isEmpty()
            ? collect()
            : MatchingChoice::query()
                ->whereIn('quiz_id', $matchingQuizIds)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('quiz_id');

        return [
            'evaluationItems' => $evaluationItems,
            'reflectionPrompts' => $reflectionPrompts,
            'mcqQuestions' => $mcqQuestions,
            'conflictCards' => $conflictCards,
            'mapEvents' => $mapEvents,
            'mapZonesByQuiz' => $mapZonesByQuiz,
            'matchingItems' => $matchingItems,
            'matchingChoicesByQuiz' => $matchingChoicesByQuiz,
        ];
    }

    /**
     * Format a QuizAttemptAnswer with its full quiz-format context for teacher inspection.
     *
     * @param  array{
     *     evaluationItems: Collection<int, EvaluationItem>,
     *     reflectionPrompts: Collection<int, ReflectionPrompt>,
     *     mcqQuestions: Collection<int, QuizQuestion>,
     *     conflictCards: Collection<int, ColorConflictCard>,
     *     mapEvents: Collection<int, ConflictMapEvent>,
     *     mapZonesByQuiz: Collection<int, Collection<int, ConflictMapZone>>,
     *     matchingItems: Collection<int, MatchingItem>,
     *     matchingChoicesByQuiz: Collection<int, Collection<int, MatchingChoice>>
     * }  $dictionaries
     * @return array<string, mixed>
     */
    public function formatAnswer(QuizAttemptAnswer $ans, array $dictionaries): array
    {
        $answerData = $ans->answer_data ?? [];
        $context = match ($ans->item_type) {
            'evaluation' => $this->buildEvaluationContext($ans, $dictionaries['evaluationItems'], $answerData),
            'reflection' => $this->buildReflectionContext($ans, $dictionaries['reflectionPrompts']),
            'multiple_choice' => $this->buildMultipleChoiceContext($ans, $dictionaries['mcqQuestions']),
            'color_conflict' => $this->buildColorConflictContext($ans, $dictionaries['conflictCards']),
            'conflict_map' => $this->buildConflictMapContext($ans, $dictionaries['mapEvents'], $dictionaries['mapZonesByQuiz']),
            'matching' => $this->buildMatchingContext($ans, $dictionaries['matchingItems'], $dictionaries['matchingChoicesByQuiz']),
            default => null,
        };

        return [
            'id' => $ans->id,
            'item_type' => $ans->item_type,
            'item_id' => $ans->item_id,
            'answer_data' => $answerData,
            'answer_text' => $ans->answer_text,
            'is_correct' => $ans->is_correct,
            'points_earned' => $ans->points_earned,
            'points_possible' => $ans->points_possible,
            'teacher_notes' => $ans->teacher_notes,
            'context' => $context,
        ];
    }

    /**
     * @param  Collection<int, EvaluationItem>  $evaluationItems
     * @param  array<string, mixed>  $answerData
     * @return array<string, mixed>|null
     */
    private function buildEvaluationContext(QuizAttemptAnswer $ans, Collection $evaluationItems, array &$answerData): ?array
    {
        $item = $evaluationItems->get($ans->item_id);
        if (! $item) {
            return null;
        }

        if (empty($answerData['stance']) && ! empty($answerData['option_id'])) {
            $selectedOpt = $item->options->firstWhere('id', (int) $answerData['option_id']);
            if ($selectedOpt) {
                $answerData['stance'] = $selectedOpt->stance_value ?: $selectedOpt->option_text;
            }
        }

        return [
            'section_title' => $item->section_title,
            'conflict_type' => $item->conflict_type,
            'question_prompt' => $item->question_prompt,
            'step1_prompt' => $item->step1_prompt,
            'sentence_template' => $item->sentence_template,
            'hint' => $item->hint,
            'options' => $item->options->map(static fn (EvaluationItemOption $opt): array => [
                'id' => $opt->id,
                'option_text' => $opt->option_text,
                'stance_value' => $opt->stance_value,
                'is_correct' => (bool) $opt->is_correct,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, ReflectionPrompt>  $reflectionPrompts
     * @return array<string, mixed>|null
     */
    private function buildReflectionContext(QuizAttemptAnswer $ans, Collection $reflectionPrompts): ?array
    {
        $prompt = $reflectionPrompts->get($ans->item_id);
        if (! $prompt) {
            return null;
        }

        return [
            'prompt_text' => $prompt->prompt_text,
            'min_words' => $prompt->min_words,
            'max_words' => $prompt->max_words,
            'guiding_hint' => $prompt->guiding_hint,
            'starter_sentence' => $prompt->starter_sentence,
        ];
    }

    /**
     * @param  Collection<int, QuizQuestion>  $mcqQuestions
     * @return array<string, mixed>|null
     */
    private function buildMultipleChoiceContext(QuizAttemptAnswer $ans, Collection $mcqQuestions): ?array
    {
        $question = $mcqQuestions->get($ans->item_id);
        if (! $question) {
            return null;
        }

        return [
            'question_text' => $question->question_text,
            'selection_type' => $question->selection_type->value,
            'passage_context' => $question->passage_context,
            'hint' => $question->hint,
            'explanation' => $question->explanation,
            'options' => $question->options->map(static fn (QuizQuestionOption $opt): array => [
                'id' => $opt->id,
                'option_text' => $opt->option_text,
                'is_correct' => (bool) $opt->is_correct,
                'feedback' => $opt->feedback,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, ColorConflictCard>  $conflictCards
     * @return array<string, mixed>|null
     */
    private function buildColorConflictContext(QuizAttemptAnswer $ans, Collection $conflictCards): ?array
    {
        $card = $conflictCards->get($ans->item_id);
        if (! $card) {
            return null;
        }

        return [
            'excerpt' => $card->excerpt,
            'correct_conflict_type' => $card->conflict_type->value,
            'correct_conflict_label' => $card->conflict_type->label(),
            'hint' => $card->hint,
            'explanation' => $card->explanation,
            'feedback_incorrect' => $card->feedback_incorrect,
            'options' => $this->defaultConflictOptions(),
        ];
    }

    /**
     * @param  Collection<int, ConflictMapEvent>  $mapEvents
     * @param  Collection<int, Collection<int, ConflictMapZone>>  $mapZonesByQuiz
     * @return array<string, mixed>|null
     */
    private function buildConflictMapContext(QuizAttemptAnswer $ans, Collection $mapEvents, Collection $mapZonesByQuiz): ?array
    {
        $event = $mapEvents->get($ans->item_id);
        if (! $event) {
            return null;
        }

        $zones = $mapZonesByQuiz->get($event->quiz_id, collect());

        return [
            'card_letter' => $event->card_letter,
            'event_text' => $event->event_text,
            'target_conflict_type' => $event->target_conflict_type->value,
            'target_conflict_label' => $event->target_conflict_type->label(),
            'hint' => $event->hint,
            'explanation' => $event->explanation,
            'zones' => $zones->isNotEmpty()
                ? $zones->map(static fn (ConflictMapZone $zone): array => [
                    'conflict_type' => $zone->conflict_type->value,
                    'label' => $zone->label,
                    'subtitle' => $zone->subtitle,
                    'color_theme' => $zone->color_theme,
                ])->values()->all()
                : array_map(static fn (ConflictType $type): array => [
                    'conflict_type' => $type->value,
                    'label' => $type->label(),
                    'subtitle' => $type->subtitle(),
                    'color_theme' => $type->colorKey(),
                ], ConflictType::cases()),
        ];
    }

    /**
     * @param  Collection<int, MatchingItem>  $matchingItems
     * @param  Collection<int, Collection<int, MatchingChoice>>  $matchingChoicesByQuiz
     * @return array<string, mixed>|null
     */
    private function buildMatchingContext(QuizAttemptAnswer $ans, Collection $matchingItems, Collection $matchingChoicesByQuiz): ?array
    {
        $item = $matchingItems->get($ans->item_id);
        if (! $item) {
            return null;
        }

        $choices = $matchingChoicesByQuiz->get($item->quiz_id, collect());

        return [
            'item_number' => $item->item_number,
            'description' => $item->description,
            'correct_letter' => $item->correctChoice?->choice_letter,
            'correct_choice_text' => $item->correctChoice?->choice_text,
            'hint' => $item->hint,
            'explanation' => $item->explanation,
            'choices' => $choices->map(static fn (MatchingChoice $choice): array => [
                'id' => $choice->id,
                'choice_letter' => $choice->choice_letter,
                'choice_text' => $choice->choice_text,
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array{value: string, label: string, subtitle: string, color_key: string}>
     */
    private function defaultConflictOptions(): array
    {
        return array_map(
            static fn (ConflictType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'subtitle' => $type->subtitle(),
                'color_key' => $type->colorKey(),
            ],
            ConflictType::cases(),
        );
    }
}
