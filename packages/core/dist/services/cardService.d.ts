import { Tag, ViewState } from '../domain/common';
import { CardDetailInputs, CardDetailOutputs, HistoryEntry, UploadReviewInputs } from '../domain/screens';
export interface CardRepository {
    fetchDetail: (cardId: string) => Promise<CardDetailOutputs>;
    updateTitle: (cardId: string, title: string) => Promise<void>;
    updateTags: (cardId: string, tags: Tag[]) => Promise<void>;
    createComment: (cardId: string, comment: string) => Promise<void>;
}
export interface DraftRepository {
    saveDraft: (draft: UploadReviewInputs) => Promise<void>;
    loadDrafts: () => Promise<UploadReviewInputs[]>;
}
export declare class CardService {
    private readonly repository;
    private readonly drafts;
    constructor(repository: CardRepository, drafts: DraftRepository);
    getDetail(inputs: CardDetailInputs): Promise<CardDetailOutputs>;
    editTitle(cardId: string, title: string): Promise<ViewState>;
    editTags(cardId: string, tags: Tag[]): Promise<ViewState>;
    comment(cardId: string, comment: string): Promise<ViewState>;
    saveDraft(draft: UploadReviewInputs): Promise<ViewState>;
    restoreDrafts(): Promise<UploadReviewInputs[]>;
}
export declare function appendHistory(history: HistoryEntry[], entry: HistoryEntry): HistoryEntry[];
//# sourceMappingURL=cardService.d.ts.map