export type TelemetryEventName = 'upload_started' | 'card_created' | 'ocr_completed' | 'tags_approved' | 'share_published' | 'share_failed' | 'template_applied' | 'synthesis_saved' | 'offline_queue_flushed';
export interface TelemetryEvent {
    name: TelemetryEventName;
    timestamp: string;
    properties: Record<string, unknown>;
}
export interface TelemetryTransport {
    send: (event: TelemetryEvent) => Promise<void>;
}
export declare class TelemetryService {
    private readonly transport;
    constructor(transport: TelemetryTransport);
    track(event: TelemetryEvent): Promise<void>;
}
export declare function startTimeToArtifact(telemetry: TelemetryService, payload: {
    filesCount: number;
    totalBytes: number;
}): Promise<void>;
export declare function completeCardCreation(telemetry: TelemetryService, payload: {
    cardId: string;
    ocrLatencyMs: number;
    tagSuggestions: number;
}): Promise<void>;
export declare function recordShareOutcome(telemetry: TelemetryService, payload: {
    cardId: string;
    success: boolean;
    reason?: string;
}): Promise<void>;
//# sourceMappingURL=telemetryService.d.ts.map