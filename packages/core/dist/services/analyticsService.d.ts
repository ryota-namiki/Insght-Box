import { ReactionSummary } from '../domain/common';
import { AnalyticsCardInputs, AnalyticsCardOutputs, AnalyticsPersonalInputs, AnalyticsPersonalOutputs, AnalyticsTimeseries } from '../domain/screens';
export interface AnalyticsDataSource {
    fetchPersonal: (inputs: AnalyticsPersonalInputs) => Promise<{
        kpis: ReactionSummary;
        timeseries: AnalyticsTimeseries[];
    }>;
    fetchCard: (inputs: AnalyticsCardInputs) => Promise<{
        kpis: ReactionSummary;
        timeseries: AnalyticsTimeseries[];
        audienceDistribution: AnalyticsCardOutputs['audienceDistribution'];
    }>;
}
export declare class AnalyticsService {
    private readonly dataSource;
    constructor(dataSource: AnalyticsDataSource);
    getPersonal(inputs: AnalyticsPersonalInputs): Promise<AnalyticsPersonalOutputs>;
    getCard(inputs: AnalyticsCardInputs): Promise<AnalyticsCardOutputs>;
    private handleError;
}
//# sourceMappingURL=analyticsService.d.ts.map