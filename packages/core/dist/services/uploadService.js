import { LIMITS } from '../domain/common';
import { assertValid, validateBatchImportInputs, validateBatchImportOutputs, validateUploadReviewInputs } from '../utils/validation';
export class UploadService {
    constructor() {
        this.queue = [];
        this.offlineDrafts = new Map();
    }
    getQueue() {
        const totalBytes = this.queue.reduce((sum, item) => sum + item.bytes, 0);
        return { items: [...this.queue], totalBytes, state: 'success' };
    }
    enqueue(files) {
        const issues = validateUploadReviewInputs(files);
        assertValid(issues);
        const currentEventCount = this.queue.filter((item) => item.eventId === files.eventId).length;
        const incomingCount = files.files.length;
        if (currentEventCount + incomingCount > LIMITS.eventUploadMax) {
            throw new Error(`このイベントは${LIMITS.eventUploadMax}件が上限です`);
        }
        files.files.forEach((file) => {
            const localId = `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
            this.queue.push({
                localId,
                filename: file.filename,
                bytes: file.bytes,
                status: 'pending',
                progress: 0,
                retryCount: 0,
                eventId: files.eventId,
                tags: files.tags,
            });
        });
        return this.getQueue();
    }
    markOfflineDraft(draftId, draft) {
        this.offlineDrafts.set(draftId, draft);
    }
    flushOfflineDrafts() {
        const drafts = Array.from(this.offlineDrafts.values());
        this.offlineDrafts.clear();
        return drafts;
    }
    async processQueue(processor) {
        for (const item of this.queue) {
            if (item.status === 'ready')
                continue;
            item.status = 'processing';
            try {
                const result = await processor(item);
                item.status = 'ready';
                item.progress = 1;
                item.error = undefined;
                if (result.tags) {
                    item.tags = result.tags.map((suggestion) => suggestion.tag);
                }
            }
            catch (error) {
                item.status = 'failed';
                item.error = error.message;
                item.retryCount += 1;
            }
        }
        const successItems = this.queue
            .filter((item) => item.status === 'ready')
            .slice(-5)
            .map((item) => ({
            id: item.localId,
            title: item.filename.slice(0, 120) || 'Untitled',
            status: 'ready',
            thumbnailUrl: undefined,
        }));
        return {
            recentCards: successItems,
            uploadQueue: [...this.queue],
            state: this.queue.some((item) => item.status === 'failed') ? 'failure' : 'success',
        };
    }
    retryFailed() {
        this.queue = this.queue.map((item) => item.status === 'failed'
            ? { ...item, status: 'pending', error: undefined }
            : item);
    }
    clearSent() {
        this.queue = this.queue.filter((item) => item.status !== 'ready');
    }
    createReviewResponse(input, processor) {
        validateUploadReviewInputs(input);
        return processor(input)
            .then(({ cardId, ocrText, suggestions }) => ({
            ocr: {
                text: ocrText,
                updatedAt: new Date().toISOString(),
            },
            tagCandidates: suggestions,
            cardId,
            state: 'success',
            snackbarMessage: `カードを作成しました: ${cardId}`,
        }))
            .catch((error) => ({
            ocr: {
                text: '',
                updatedAt: new Date().toISOString(),
            },
            tagCandidates: [],
            state: 'failure',
            snackbarMessage: error.message,
        }));
    }
    planBatchImport(input) {
        const issues = validateBatchImportInputs(input);
        assertValid(issues);
        const previews = input.files.map((file) => {
            const generatedCards = input.splitStrategy === 'file' ? 1 : file.pages ?? 1;
            const blocked = generatedCards + this.queue.length > LIMITS.eventUploadMax;
            return {
                fileId: file.id,
                filename: file.filename,
                pages: file.pages ?? 1,
                generatedCards,
                blocked,
                reason: blocked ? `イベント上限${LIMITS.eventUploadMax}件を超過します` : undefined,
            };
        });
        const result = {
            previews,
            state: previews.some((preview) => preview.blocked) ? 'failure' : 'success',
        };
        validateBatchImportOutputs(result);
        return result;
    }
}
export function normalizeTags(input) {
    return input
        .map((label) => label.trim())
        .filter(Boolean)
        .slice(0, LIMITS.tagsMaxPerCard)
        .map((label) => ({
        id: label.replace(/\s+/g, '-').toLowerCase(),
        label,
    }));
}
//# sourceMappingURL=uploadService.js.map