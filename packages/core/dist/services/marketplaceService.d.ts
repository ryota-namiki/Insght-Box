import { SortOrder } from '../domain/common';
import { MarketplaceCard, MarketplaceInputs, MarketplaceOutputs } from '../domain/screens';
export interface MarketplaceDataSource {
    fetchCards: (params: {
        tab: MarketplaceInputs['tab'];
        sort: SortOrder;
        eventId?: string;
    }) => Promise<MarketplaceCard[]>;
}
export declare class MarketplaceService {
    private readonly dataSource;
    constructor(dataSource: MarketplaceDataSource);
    getCards(inputs: MarketplaceInputs): Promise<MarketplaceOutputs>;
}
export declare function computeTrendingScore(card: MarketplaceCard): number;
export declare function sortCards(cards: MarketplaceCard[], sort: SortOrder): MarketplaceCard[];
//# sourceMappingURL=marketplaceService.d.ts.map