import { validateMarketplaceInputs } from '../utils/validation';
export class MarketplaceService {
    constructor(dataSource) {
        this.dataSource = dataSource;
    }
    async getCards(inputs) {
        const issues = validateMarketplaceInputs(inputs);
        if (issues.length) {
            return { cards: [], state: 'failure' };
        }
        try {
            const cards = await this.dataSource.fetchCards(inputs);
            return { cards, state: 'success' };
        }
        catch (error) {
            if (/offline/i.test(error.message)) {
                return { cards: [], state: 'offline' };
            }
            return { cards: [], state: 'failure' };
        }
    }
}
export function computeTrendingScore(card) {
    const reactions = card.reactions;
    return reactions.views * 0.4 + reactions.comments * 0.4 + reactions.likes * 0.2;
}
export function sortCards(cards, sort) {
    if (sort === 'latest') {
        return [...cards].sort((a, b) => (a.summary.createdAt < b.summary.createdAt ? 1 : -1));
    }
    return [...cards].sort((a, b) => computeTrendingScore(b) - computeTrendingScore(a));
}
//# sourceMappingURL=marketplaceService.js.map