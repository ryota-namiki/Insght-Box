export class TelemetryService {
    constructor(transport) {
        this.transport = transport;
    }
    async track(event) {
        try {
            await this.transport.send(event);
        }
        catch (error) {
            if (/offline/i.test(error.message)) {
                console.info('Telemetry queued due to offline state', event);
                return;
            }
            console.warn('Telemetry send failed', event, error);
        }
    }
}
export function startTimeToArtifact(telemetry, payload) {
    return telemetry.track({
        name: 'upload_started',
        timestamp: new Date().toISOString(),
        properties: payload,
    });
}
export function completeCardCreation(telemetry, payload) {
    return telemetry.track({
        name: 'card_created',
        timestamp: new Date().toISOString(),
        properties: payload,
    });
}
export function recordShareOutcome(telemetry, payload) {
    return telemetry.track({
        name: payload.success ? 'share_published' : 'share_failed',
        timestamp: new Date().toISOString(),
        properties: payload,
    });
}
//# sourceMappingURL=telemetryService.js.map