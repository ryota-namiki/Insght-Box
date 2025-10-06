import { LIMITS, Tag, ViewState } from '../domain/common';
import {
  BatchImportInputs,
  BatchImportOutputs,
  BatchImportPreview,
  TagSuggestion,
  UploadHomeOutputs,
  UploadQueueItem,
  UploadReviewInputs,
  UploadReviewOutputs,
} from '../domain/screens';
import { assertValid, guard, validateBatchImportInputs, validateBatchImportOutputs, validateUploadReviewInputs } from '../utils/validation';

export type UploadQueueState = {
  items: UploadQueueItem[];
  totalBytes: number;
  state: ViewState;
};

export class UploadService {
  private queue: UploadQueueItem[] = [];
  private offlineDrafts: Map<string, UploadReviewInputs> = new Map();

  getQueue(): UploadQueueState {
    const totalBytes = this.queue.reduce((sum, item) => sum + item.bytes, 0);
    return { items: [...this.queue], totalBytes, state: 'success' };
  }

  enqueue(files: UploadReviewInputs): UploadQueueState {
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

  markOfflineDraft(draftId: string, draft: UploadReviewInputs): void {
    this.offlineDrafts.set(draftId, draft);
  }

  flushOfflineDrafts(): UploadReviewInputs[] {
    const drafts = Array.from(this.offlineDrafts.values());
    this.offlineDrafts.clear();
    return drafts;
  }

  async processQueue(processor: (item: UploadQueueItem) => Promise<{ cardId: string; ocrText?: string; tags?: TagSuggestion[] }>): Promise<UploadHomeOutputs> {
    for (const item of this.queue) {
      if (item.status === 'ready') continue;
      item.status = 'processing';
      try {
        const result = await processor(item);
        item.status = 'ready';
        item.progress = 1;
        item.error = undefined;
        if (result.tags) {
          item.tags = result.tags.map((suggestion) => suggestion.tag);
        }
      } catch (error) {
        item.status = 'failed';
        item.error = (error as Error).message;
        item.retryCount += 1;
      }
    }

    const successItems = this.queue
      .filter((item) => item.status === 'ready')
      .slice(-5)
      .map((item) => ({
        id: item.localId,
        title: item.filename.slice(0, 120) || 'Untitled',
        status: 'ready' as const,
        thumbnailUrl: undefined,
      }));
    return {
      recentCards: successItems,
      uploadQueue: [...this.queue],
      state: this.queue.some((item) => item.status === 'failed') ? 'failure' : 'success',
    };
  }

  retryFailed(): void {
    this.queue = this.queue.map((item) =>
      item.status === 'failed'
        ? { ...item, status: 'pending', error: undefined }
        : item,
    );
  }

  clearSent(): void {
    this.queue = this.queue.filter((item) => item.status !== 'ready');
  }

  createReviewResponse(input: UploadReviewInputs, processor: (input: UploadReviewInputs) => Promise<{ cardId: string; ocrText: string; suggestions: TagSuggestion[] }>): Promise<UploadReviewOutputs> {
    validateUploadReviewInputs(input);
    return processor(input)
      .then(({ cardId, ocrText, suggestions }) => ({
        ocr: {
          text: ocrText,
          updatedAt: new Date().toISOString(),
        },
        tagCandidates: suggestions,
        cardId,
        state: 'success' as const,
        snackbarMessage: `カードを作成しました: ${cardId}`,
      }))
      .catch((error: Error) => ({
        ocr: {
          text: '',
          updatedAt: new Date().toISOString(),
        },
        tagCandidates: [],
        state: 'failure' as const,
        snackbarMessage: error.message,
      }));
  }

  planBatchImport(input: BatchImportInputs): BatchImportOutputs {
    const issues = validateBatchImportInputs(input);
    assertValid(issues);

    const previews: BatchImportPreview[] = input.files.map((file) => {
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

    const result: BatchImportOutputs = {
      previews,
      state: previews.some((preview) => preview.blocked) ? 'failure' : 'success',
    };

    validateBatchImportOutputs(result);
    return result;
  }
}

export function normalizeTags(input: string[]): Tag[] {
  return input
    .map((label) => label.trim())
    .filter(Boolean)
    .slice(0, LIMITS.tagsMaxPerCard)
    .map((label) => ({
      id: label.replace(/\s+/g, '-').toLowerCase(),
      label,
    }));
}
