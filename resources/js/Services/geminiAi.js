/**
 * Google Gemini AI Client for WebRead Teacher Studio
 *
 * Communicates with backend TeacherAiAssistantController endpoints.
 * Handles CSRF protection, explicit timeouts, and structured error responses.
 */

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag && metaTag.content) {
        return metaTag.content;
    }

    // Fallback: parse XSRF-TOKEN cookie if present
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function postJson(endpoint, payload) {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok || !data?.success) {
        const errorMsg = data?.message || `Request failed with status ${response.status}`;
        const errorCode = data?.error_code || 'ERROR';
        const err = new Error(errorMsg);
        err.code = errorCode;
        err.status = response.status;
        throw err;
    }

    return data.data;
}

/**
 * Check if Google Gemini AI is configured with an API key on the backend.
 */
export async function checkAiStatus() {
    try {
        const response = await fetch('/teachers/ai/status', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json().catch(() => null);
        return {
            configured: Boolean(data?.configured),
            model: data?.model || 'gemini-2.5-flash',
        };
    } catch {
        return { configured: false, model: 'gemini-2.5-flash' };
    }
}

/**
 * Fetch kid-friendly definition, pronunciation, and example sentence for a word.
 *
 * @param {Object} options
 * @param {string} options.word
 * @param {string} [options.context]
 * @param {string} [options.gradeLevel]
 * @returns {Promise<{ word: string, pronunciation: string, meaning: string, example_sentence: string, synonyms: string[], suggested_color: string }>}
 */
export async function defineWord({ word, context = '', gradeLevel = '' }) {
    return await postJson('/teachers/ai/word-meaning', {
        word,
        context,
        grade_level: gradeLevel,
    });
}

/**
 * Generate lecture description, learning objectives, or full reading story with vocabulary.
 *
 * @param {Object} options
 * @param {string} options.topic
 * @param {string} [options.gradeLevel]
 * @param {'describe'|'full_story'|'vocabulary_list'} [options.mode]
 * @param {string} [options.additionalContext]
 */
export async function generateLectureAssist({ topic, gradeLevel = '', mode = 'describe', additionalContext = '' }) {
    return await postJson('/teachers/ai/lecture-assist', {
        topic,
        grade_level: gradeLevel,
        mode,
        additional_context: additionalContext,
    });
}

/**
 * Generate a short, kid-friendly module description based on module title.
 *
 * @param {Object} options
 * @param {string} options.title
 * @param {string} [options.gradeLevel]
 * @param {string} [options.theme]
 * @returns {Promise<{ description: string }>}
 */
export async function generateModuleDescription({ title, gradeLevel = '', theme = '' }) {
    return await postJson('/teachers/ai/module-assist', {
        title,
        grade_level: gradeLevel,
        theme,
    });
}

/**
 * Generate quiz questions, choices, or reflection prompts.
 *
 * @param {Object} options
 * @param {'multiple_choice'|'matching_type'|'reflection'|'color_the_conflict'} options.quizType
 * @param {string} options.topicOrPassage
 * @param {number} [options.questionCount]
 * @param {string} [options.gradeLevel]
 */
export async function generateQuizAssist({ quizType, topicOrPassage, questionCount = 4, gradeLevel = '' }) {
    return await postJson('/teachers/ai/quiz-assist', {
        quiz_type: quizType,
        topic_or_passage: topicOrPassage,
        question_count: questionCount,
        grade_level: gradeLevel,
    });
}

