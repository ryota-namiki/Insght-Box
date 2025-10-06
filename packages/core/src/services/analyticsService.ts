import { ReactionSummary, ViewState } from '../domain/common';
import {
  AnalyticsCardInputs,
  AnalyticsCardOutputs,
  AnalyticsPersonalInputs,
  AnalyticsPersonalOutputs,
  AnalyticsTimeseries,
} from '../domain/screens';

export interface AnalyticsDataSource {
  fetchPersonal: (inputs: AnalyticsPersonalInputs) => Promise<{ kpis: ReactionSummary; timeseries: AnalyticsTimeseries[] }>;
  fetchCard: (inputs: AnalyticsCardInputs) => Promise<{ kpis: ReactionSummary; timeseries: AnalyticsTimeseries[]; audienceDistribution: AnalyticsCardOutputs['audienceDistribution']; }>;
}

export class AnalyticsService {
  constructor(private readonly dataSource: AnalyticsDataSource) {}

  async getPersonal(inputs: AnalyticsPersonalInputs): Promise<AnalyticsPersonalOutputs> {
    try {
      const data = await this.dataSource.fetchPersonal(inputs);
      return { ...data, state: 'success' };
    } catch (error) {
      return this.handleError<AnalyticsPersonalOutputs>(error, { kpis: { views: 0, comments: 0, likes: 0 }, timeseries: [], state: 'failure' });
    }
  }

  async getCard(inputs: AnalyticsCardInputs): Promise<AnalyticsCardOutputs> {
    try {
      const data = await this.dataSource.fetchCard(inputs);
      return { ...data, state: 'success' };
    } catch (error) {
      return this.handleError<AnalyticsCardOutputs>(error, { kpis: { views: 0, comments: 0, likes: 0 }, timeseries: [], audienceDistribution: [], state: 'failure' });
    }
  }

  private handleError<T extends { state: ViewState }>(error: unknown, fallback: T): T {
    if (/offline/i.test((error as Error).message)) {
      return { ...fallback, state: 'offline' };
    }
    return { ...fallback, state: 'failure' };
  }
}
