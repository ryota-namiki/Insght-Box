import { SortOrder } from '../domain/common';
import { MarketplaceCard, MarketplaceInputs, MarketplaceOutputs } from '../domain/screens';
import { validateMarketplaceInputs } from '../utils/validation';

export interface MarketplaceDataSource {
  fetchCards: (params: {
    tab: MarketplaceInputs['tab'];
    sort: SortOrder;
    eventId?: string;
  }) => Promise<MarketplaceCard[]>;
}

export class MarketplaceService {
  constructor(private readonly dataSource: MarketplaceDataSource) {}

  async getCards(inputs: MarketplaceInputs): Promise<MarketplaceOutputs> {
    const issues = validateMarketplaceInputs(inputs);
    if (issues.length) {
      return { cards: [], state: 'failure' };
    }

    try {
      const cards = await this.dataSource.fetchCards(inputs);
      return { cards, state: 'success' };
    } catch (error) {
      if (/offline/i.test((error as Error).message)) {
        return { cards: [], state: 'offline' };
      }
      return { cards: [], state: 'failure' };
    }
  }
}

export function computeTrendingScore(card: MarketplaceCard): number {
  const reactions = card.reactions;
  return reactions.views * 0.4 + reactions.comments * 0.4 + reactions.likes * 0.2;
}

export function sortCards(cards: MarketplaceCard[], sort: SortOrder): MarketplaceCard[] {
  if (sort === 'latest') {
    return [...cards].sort((a, b) => (a.summary.createdAt < b.summary.createdAt ? 1 : -1));
  }
  return [...cards].sort((a, b) => computeTrendingScore(b) - computeTrendingScore(a));
}
