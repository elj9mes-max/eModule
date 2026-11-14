import { computed, markRaw, reactive } from 'vue';

/**
 * Global background save queue.
 *
 * State lives at module level so it survives Inertia page navigation: a save started on one
 * page keeps uploading (and stays visible in the SaveQueuePanel) after the teacher leaves it.
 * Requests use plain XHR instead of Inertia visits so a finished save never swaps the current page.
 */
const state = reactive({
    jobs: [],
    isPanelOpen: false,
    isRunning: false,
});

const finishListeners = new Set();
let jobCounter = 0;
let hideTimer = null;
let isUnloadGuardInstalled = false;

const pendingCount = computed(() => state.jobs.filter((job) => job.status === 'queued' || job.status === 'uploading').length);

const overallProgress = computed(() => {
    if (state.jobs.length === 0) {
        return 0;
    }

    return Math.round(state.jobs.reduce((sum, job) => sum + job.progress, 0) / state.jobs.length);
});

const readXsrfToken = () => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    if (match) {
        return { name: 'X-XSRF-TOKEN', value: decodeURIComponent(match[1]) };
    }

    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? { name: 'X-CSRF-TOKEN', value: meta.getAttribute('content') } : null;
};

const toFormData = (payload) => {
    const formData = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        if (value instanceof Blob) {
            formData.append(key, value);
        } else if (typeof value === 'boolean') {
            formData.append(key, value ? '1' : '0');
        } else if (value === null || value === undefined) {
            formData.append(key, '');
        } else if (typeof value === 'object') {
            formData.append(key, JSON.stringify(value));
        } else {
            formData.append(key, String(value));
        }
    });

    return formData;
};

const notifyFinished = (job, result) => {
    finishListeners.forEach((listener) => {
        try {
            listener(job, result);
        } catch (error) {
            console.error(error);
        }
    });
};

const installUnloadGuard = () => {
    if (isUnloadGuardInstalled || typeof window === 'undefined') {
        return;
    }

    isUnloadGuardInstalled = true;
    window.addEventListener('beforeunload', (event) => {
        if (pendingCount.value > 0) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
};

const jobUrl = (job) => (job.type === 'save' && job.contentId ? `${job.baseUrl}/${job.contentId}` : job.baseUrl);

/**
 * Placeholder written into saved HTML for media whose upload job has not finished yet.
 * Uploads always run before later saves, so the token is swapped for the real URL at send time.
 *
 * @param {number} uploadJobId
 * @returns {string}
 */
const pendingUploadToken = (uploadJobId) => `pending-upload:${uploadJobId}`;

const findJob = (jobId) => state.jobs.find((job) => job.id === jobId) || null;

const uploadedUrls = new Map();

/**
 * Swap pending-upload tokens in string payload fields for the uploaded file URLs.
 *
 * @returns {{ payload: Record<string, unknown>, error: string }}
 */
const resolvePendingUploads = (payload) => {
    let error = '';
    const resolved = {};

    Object.entries(payload).forEach(([key, value]) => {
        if (typeof value !== 'string' || !value.includes('pending-upload:')) {
            resolved[key] = value;

            return;
        }

        resolved[key] = value.replace(/pending-upload:(\d+)/g, (token, rawId) => {
            const uploadJobId = Number(rawId);
            if (uploadedUrls.has(uploadJobId)) {
                return uploadedUrls.get(uploadJobId);
            }

            const uploadJob = findJob(uploadJobId);
            error = uploadJob
                ? `"${uploadJob.label}" has not uploaded yet. Retry that upload first.`
                : 'A media file on this page never finished uploading. Re-insert it and save again.';

            return token;
        });
    });

    return { payload: resolved, error };
};

/**
 * @param {number} uploadJobId
 * @returns {string|null}
 */
const uploadedUrlFor = (uploadJobId) => uploadedUrls.get(uploadJobId) || null;

const runJob = (job) => new Promise((resolve) => {
    job.status = 'uploading';
    job.progress = 1;

    const wasCreate = job.type === 'save' && !job.contentId;
    let requestPayload = job.payload;

    if (job.type === 'save') {
        const { payload, error } = resolvePendingUploads(job.payload);
        if (error) {
            job.status = 'failed';
            job.error = error;
            notifyFinished(job, { ok: false, errors: {}, wasCreate });
            resolve();

            return;
        }
        requestPayload = payload;
    }
    const xhr = new XMLHttpRequest();
    let serverWaitTimer = null;

    // Upload covers 0–90%; the rest creeps forward while the server processes the request.
    const startServerWait = () => {
        if (serverWaitTimer) {
            return;
        }
        serverWaitTimer = window.setInterval(() => {
            if (job.progress < 99) {
                job.progress += 1;
            }
        }, 250);
    };

    const finish = (result) => {
        window.clearInterval(serverWaitTimer);
        notifyFinished(job, result);
        resolve();
    };

    const fail = (message, errors = {}) => {
        job.status = 'failed';
        job.error = message;
        finish({ ok: false, errors, wasCreate });
    };

    xhr.open('POST', jobUrl(job));
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    const token = readXsrfToken();
    if (token) {
        xhr.setRequestHeader(token.name, token.value);
    }

    xhr.upload.onprogress = (event) => {
        if (event.lengthComputable) {
            job.progress = Math.max(job.progress, Math.min(90, Math.round((event.loaded / event.total) * 90)));
        }
    };
    xhr.upload.onload = () => {
        job.progress = Math.max(job.progress, 90);
        startServerWait();
    };

    xhr.onload = () => {
        let body = null;
        try {
            body = JSON.parse(xhr.responseText);
        } catch {
            body = null;
        }

        if (job.type === 'upload' && xhr.status >= 200 && xhr.status < 300 && body?.url) {
            job.status = 'done';
            job.progress = 100;
            uploadedUrls.set(job.id, body.url);
            finish({ ok: true, url: body.url, wasCreate: false });

            return;
        }

        if (job.type === 'save' && xhr.status >= 200 && xhr.status < 300 && body?.id) {
            job.status = 'done';
            job.progress = 100;

            if (wasCreate) {
                job.contentId = body.id;
                // Later queued saves of the same draft must update this page instead of creating duplicates.
                state.jobs
                    .filter((queued) => queued.status === 'queued' && queued.draftKey === job.draftKey && !queued.contentId)
                    .forEach((queued) => {
                        queued.contentId = body.id;
                    });
            }

            finish({ ok: true, id: body.id, wasCreate });

            return;
        }

        if (xhr.status === 422) {
            const errors = Object.fromEntries(
                Object.entries(body?.errors || {}).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
            );
            fail(Object.values(errors)[0] || body?.message || 'Validation failed.', errors);

            return;
        }

        if (xhr.status === 413) {
            fail('File is larger than PHP allows. Raise post_max_size and upload_max_filesize in php.ini.');

            return;
        }

        if (xhr.status === 419) {
            fail('Session expired. Refresh the page and retry.');

            return;
        }

        fail(body?.message || `Server error (${xhr.status}). Please retry.`);
    };

    xhr.onerror = () => fail('Network error. Please retry.');
    xhr.onabort = () => fail('Save was cancelled.');

    xhr.send(toFormData(requestPayload));
});

const processQueue = async () => {
    if (state.isRunning) {
        return;
    }

    state.isRunning = true;

    let job = state.jobs.find((queued) => queued.status === 'queued');
    while (job) {
        await runJob(job);
        job = state.jobs.find((queued) => queued.status === 'queued');
    }

    state.isRunning = false;

    if (state.jobs.length > 0 && state.jobs.every((finished) => finished.status === 'done')) {
        hideTimer = window.setTimeout(() => {
            state.isPanelOpen = false;
            state.jobs = [];
        }, 3500);
    }
};

/**
 * Queue a multipart POST to run in the background.
 * "save" jobs post page content (and get a content id back); "upload" jobs post one media file (and get a URL back).
 *
 * @param {{ type?: 'save'|'upload', label: string, baseUrl: string, contentId?: number|null, draftKey?: string|null, payload: Record<string, unknown>, meta?: Record<string, unknown> }} options
 * @returns {number} The queued job id.
 */
const enqueue = ({ type = 'save', label, baseUrl, contentId = null, draftKey = null, payload, meta = {} }) => {
    installUnloadGuard();

    const id = ++jobCounter;
    state.jobs.push({
        id,
        type,
        label: label || 'Untitled Page',
        baseUrl,
        contentId,
        draftKey,
        payload: markRaw({ ...payload }),
        meta: markRaw({ ...meta }),
        status: 'queued',
        progress: 0,
        error: '',
    });

    window.clearTimeout(hideTimer);
    state.isPanelOpen = true;
    processQueue();

    return id;
};

const retry = (job) => {
    job.status = 'queued';
    job.progress = 0;
    job.error = '';
    window.clearTimeout(hideTimer);
    processQueue();
};

const dismiss = (job) => {
    state.jobs = state.jobs.filter((queued) => queued.id !== job.id);
    if (state.jobs.length === 0) {
        state.isPanelOpen = false;
    }
};

const closePanel = () => {
    state.isPanelOpen = false;
    state.jobs = state.jobs.filter((queued) => queued.status === 'queued' || queued.status === 'uploading');
};

/**
 * Subscribe to finished saves. Returns an unsubscribe function — call it on unmount.
 *
 * @param {(job: object, result: { ok: boolean, id?: number, wasCreate: boolean, errors?: Record<string, string> }) => void} listener
 * @returns {() => void}
 */
const onJobFinished = (listener) => {
    finishListeners.add(listener);

    return () => finishListeners.delete(listener);
};

const hasPendingForDraft = (draftKey) => state.jobs.some((job) => job.status === 'queued' && job.draftKey === draftKey);

export function useSaveQueue() {
    return {
        state,
        pendingCount,
        overallProgress,
        enqueue,
        retry,
        dismiss,
        closePanel,
        onJobFinished,
        hasPendingForDraft,
        pendingUploadToken,
        uploadedUrlFor,
    };
}
