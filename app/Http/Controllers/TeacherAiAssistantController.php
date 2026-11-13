<?php

namespace App\Http\Controllers;

use App\Exceptions\Gemini\GeminiApiException;
use App\Exceptions\Gemini\GeminiNotConfiguredException;
use App\Exceptions\Gemini\GeminiQuotaExceededException;
use App\Http\Requests\TeacherAiLectureAssistRequest;
use App\Http\Requests\TeacherAiModuleAssistRequest;
use App\Http\Requests\TeacherAiQuizAssistRequest;
use App\Http\Requests\TeacherAiWordMeaningRequest;
use App\Services\Ai\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeacherAiAssistantController extends Controller
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    /**
     * Check if Gemini AI service is configured and ready.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isTeacher()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'configured' => $this->geminiService->isConfigured(),
            'model' => (string) config('services.gemini.model', 'gemini-3.5-flash-lite'),
        ]);
    }

    /**
     * Define a word or phrase with kid-friendly meaning and pronunciation.
     */
    public function wordMeaning(TeacherAiWordMeaningRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $definition = $this->geminiService->defineWord(
                word: (string) $validated['word'],
                context: $validated['context'] ?? null,
                gradeLevel: $validated['grade_level'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $definition,
            ]);
        } catch (GeminiNotConfiguredException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'NOT_CONFIGURED',
                'message' => $e->getMessage(),
            ], 422);
        } catch (GeminiQuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'QUOTA_EXCEEDED',
                'message' => $e->getMessage(),
            ], 429);
        } catch (GeminiApiException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'API_ERROR',
                'message' => $e->getMessage(),
            ], $e->statusCode >= 400 && $e->statusCode < 600 ? $e->statusCode : 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in TeacherAiAssistantController@wordMeaning', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message' => 'Unable to define word right now. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate lecture descriptions, outlines, reading stories, and vocabulary glosses.
     */
    public function lectureAssist(TeacherAiLectureAssistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->geminiService->generateLectureAssist(
                topic: (string) $validated['topic'],
                gradeLevel: $validated['grade_level'] ?? null,
                mode: (string) ($validated['mode'] ?? 'describe'),
                additionalContext: $validated['additional_context'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (GeminiNotConfiguredException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'NOT_CONFIGURED',
                'message' => $e->getMessage(),
            ], 422);
        } catch (GeminiQuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'QUOTA_EXCEEDED',
                'message' => $e->getMessage(),
            ], 429);
        } catch (GeminiApiException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'API_ERROR',
                'message' => $e->getMessage(),
            ], $e->statusCode >= 400 && $e->statusCode < 600 ? $e->statusCode : 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in TeacherAiAssistantController@lectureAssist', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message' => 'Unable to generate lecture assistance right now. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate module description based on module title.
     */
    public function moduleAssist(TeacherAiModuleAssistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->geminiService->generateModuleDescription(
                title: (string) $validated['title'],
                gradeLevel: $validated['grade_level'] ?? null,
                theme: $validated['theme'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (GeminiNotConfiguredException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'NOT_CONFIGURED',
                'message' => $e->getMessage(),
            ], 422);
        } catch (GeminiQuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'QUOTA_EXCEEDED',
                'message' => $e->getMessage(),
            ], 429);
        } catch (GeminiApiException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'API_ERROR',
                'message' => $e->getMessage(),
            ], $e->statusCode >= 400 && $e->statusCode < 600 ? $e->statusCode : 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in TeacherAiAssistantController@moduleAssist', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message' => 'Unable to generate module description right now. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate quiz questions, choices, or reflection prompts.
     */
    public function quizAssist(TeacherAiQuizAssistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->geminiService->generateQuizAssist(
                quizType: (string) $validated['quiz_type'],
                topicOrPassage: (string) $validated['topic_or_passage'],
                questionCount: (int) ($validated['question_count'] ?? 4),
                gradeLevel: $validated['grade_level'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (GeminiNotConfiguredException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'NOT_CONFIGURED',
                'message' => $e->getMessage(),
            ], 422);
        } catch (GeminiQuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'QUOTA_EXCEEDED',
                'message' => $e->getMessage(),
            ], 429);
        } catch (GeminiApiException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'API_ERROR',
                'message' => $e->getMessage(),
            ], $e->statusCode >= 400 && $e->statusCode < 600 ? $e->statusCode : 502);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in TeacherAiAssistantController@quizAssist', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message' => 'Unable to generate quiz questions right now. Please try again.',
            ], 500);
        }
    }
}
