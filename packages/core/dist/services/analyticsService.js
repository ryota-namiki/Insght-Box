export class AnalyticsService {
    constructor(dataSource) {
        this.dataSource = dataSource;
    }
    async getPersonal(inputs) {
        try {
            const data = await this.dataSource.fetchPersonal(inputs);
            return { ...data, state: 'success' };
        }
        catch (error) {
            return this.handleError(error, { kpis: { views: 0, comments: 0, likes: 0 }, timeseries: [], state: 'failure' });
        }
    }
    async getCard(inputs) {
        try {
            const data = await this.dataSource.fetchCard(inputs);
            return { ...data, state: 'success' };
        }
        catch (error) {
            return this.handleError(error, { kpis: { views: 0, comments: 0, likes: 0 }, timeseries: [], audienceDistribution: [], state: 'failure' });
        }
    }
    handleError(error, fallback) {
        if (/offline/i.test(error.message)) {
            return { ...fallback, state: 'offline' };
        }
        return { ...fallback, state: 'failure' };
    }
}
//# sourceMappingURL=analyticsService.js.map