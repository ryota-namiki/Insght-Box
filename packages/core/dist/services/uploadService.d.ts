import { Tag, ViewState } from '../domain/common';
import { BatchImportInputs, BatchImportOutputs, TagSuggestion, UploadHomeOutputs, UploadQueueItem, UploadReviewInputs, UploadReviewOutputs } from '../domain/screens';
export type UploadQueueState = {
    items: UploadQueueItem[];
    totalBytes: number;
    state: ViewState;
};
export declare class UploadService {
    private queue;
    private offlineDrafts;
    getQueue(): UploadQueueState;
    enqueue(files: UploadReviewInputs): UploadQueueState;
    markOfflineDraft(draftId: string, draft: UploadReviewInputs): void;
    flushOfflineDrafts(): UploadReviewInputs[];
    processQueue(processor: (item: UploadQueueItem) => Promise<{
        cardId: string;
        ocrText?: string;
        tags?: TagSuggestion[];
    }>): Promise<UploadHomeOutputs>;
    retryFailed(): void;
    clearSent(): void;
    createReviewResponse(input: UploadReviewInputs, processor: (input: UploadReviewInputs) => Promise<{
        cardId: string;
        ocrText: string;
        suggestions: TagSuggestion[];
    }>): Promise<UploadReviewOutputs>;
    planBatchImport(input: BatchImportInputs): BatchImportOutputs;
}
export declare function normalizeTags(input: string[]): Tag[];
//# sourceMappingURL=uploadService.d.ts.map