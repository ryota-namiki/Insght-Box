import { LIMITS, Tag, ViewState } from '../domain/common';
import {
  CardDetailInputs,
  CardDetailOutputs,
  HistoryEntry,
  UploadReviewInputs,
} from '../domain/screens';
import { assertValid, validateCardDetail, validateUploadReviewInputs } from '../utils/validation';

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

export class CardService {
  constructor(private readonly repository: CardRepository, private readonly drafts: DraftRepository) {}

  async getDetail(inputs: CardDetailInputs): Promise<CardDetailOutputs> {
    const outputs = await this.repository.fetchDetail(inputs.cardId);
    const issues = validateCardDetail(inputs, outputs);
    assertValid(issues);
    return outputs;
  }

  async editTitle(cardId: string, title: string): Promise<ViewState> {
    if (!title || title.length > LIMITS.titleMax) {
      return 'failure';
    }
    try {
      await this.repository.updateTitle(cardId, title);
      return 'success';
    } catch (error) {
      return /offline/i.test((error as Error).message) ? 'offline' : 'failure';
    }
  }

  async editTags(cardId: string, tags: Tag[]): Promise<ViewState> {
    if (tags.length > LIMITS.tagsMaxPerCard) {
      return 'failure';
    }
    try {
      await this.repository.updateTags(cardId, tags);
      return 'success';
    } catch (error) {
      return /offline/i.test((error as Error).message) ? 'offline' : 'failure';
    }
  }

  async comment(cardId: string, comment: string): Promise<ViewState> {
    if (!comment.trim() || comment.length > 500) {
      return 'failure';
    }
    try {
      await this.repository.createComment(cardId, comment.trim());
      return 'success';
    } catch (error) {
      return /offline/i.test((error as Error).message) ? 'offline' : 'failure';
    }
  }

  async saveDraft(draft: UploadReviewInputs): Promise<ViewState> {
    const issues = validateUploadReviewInputs(draft);
    if (issues.length) {
      return 'failure';
    }
    try {
      await this.drafts.saveDraft(draft);
      return 'success';
    } catch (error) {
      return 'failure';
    }
  }

  async restoreDrafts(): Promise<UploadReviewInputs[]> {
    return this.drafts.loadDrafts();
  }
}

export function appendHistory(history: HistoryEntry[], entry: HistoryEntry): HistoryEntry[] {
  const next = [...history, entry];
  return next.slice(-50);
}
