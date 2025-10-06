import { LIMITS } from '../domain/common';
import { assertValid, validateCardDetail, validateUploadReviewInputs } from '../utils/validation';
export class CardService {
    constructor(repository, drafts) {
        this.repository = repository;
        this.drafts = drafts;
    }
    async getDetail(inputs) {
        const outputs = await this.repository.fetchDetail(inputs.cardId);
        const issues = validateCardDetail(inputs, outputs);
        assertValid(issues);
        return outputs;
    }
    async editTitle(cardId, title) {
        if (!title || title.length > LIMITS.titleMax) {
            return 'failure';
        }
        try {
            await this.repository.updateTitle(cardId, title);
            return 'success';
        }
        catch (error) {
            return /offline/i.test(error.message) ? 'offline' : 'failure';
        }
    }
    async editTags(cardId, tags) {
        if (tags.length > LIMITS.tagsMaxPerCard) {
            return 'failure';
        }
        try {
            await this.repository.updateTags(cardId, tags);
            return 'success';
        }
        catch (error) {
            return /offline/i.test(error.message) ? 'offline' : 'failure';
        }
    }
    async comment(cardId, comment) {
        if (!comment.trim() || comment.length > 500) {
            return 'failure';
        }
        try {
            await this.repository.createComment(cardId, comment.trim());
            return 'success';
        }
        catch (error) {
            return /offline/i.test(error.message) ? 'offline' : 'failure';
        }
    }
    async saveDraft(draft) {
        const issues = validateUploadReviewInputs(draft);
        if (issues.length) {
            return 'failure';
        }
        try {
            await this.drafts.saveDraft(draft);
            return 'success';
        }
        catch (error) {
            return 'failure';
        }
    }
    async restoreDrafts() {
        return this.drafts.loadDrafts();
    }
}
export function appendHistory(history, entry) {
    const next = [...history, entry];
    return next.slice(-50);
}
//# sourceMappingURL=cardService.js.map