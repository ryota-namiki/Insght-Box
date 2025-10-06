export type TelemetryEventName =
  | 'upload_started'
  | 'card_created'
  | 'ocr_completed'
  | 'tags_approved'
  | 'share_published'
  | 'share_failed'
  | 'template_applied'
  | 'synthesis_saved'
  | 'offline_queue_flushed';

export interface TelemetryEvent {
  name: TelemetryEventName;
  timestamp: string;
  properties: Record<string, unknown>;
}

export interface TelemetryTransport {
  send: (event: TelemetryEvent) => Promise<void>;
}

export class TelemetryService {
  constructor(private readonly transport: TelemetryTransport) {}

  async track(event: TelemetryEvent): Promise<void> {
    try {
      await this.transport.send(event);
    } catch (error) {
      if (/offline/i.test((error as Error).message)) {
        console.info('Telemetry queued due to offline state', event);
        return;
      }
      console.warn('Telemetry send failed', event, error);
    }
  }
}

export function startTimeToArtifact(telemetry: TelemetryService, payload: { filesCount: number; totalBytes: number }): Promise<void> {
  return telemetry.track({
    name: 'upload_started',
    timestamp: new Date().toISOString(),
    properties: payload,
  });
}

export function completeCardCreation(
  telemetry: TelemetryService,
  payload: { cardId: string; ocrLatencyMs: number; tagSuggestions: number },
): Promise<void> {
  return telemetry.track({
    name: 'card_created',
    timestamp: new Date().toISOString(),
    properties: payload,
  });
}

export function recordShareOutcome(
  telemetry: TelemetryService,
  payload: { cardId: string; success: boolean; reason?: string },
): Promise<void> {
  return telemetry.track({
    name: payload.success ? 'share_published' : 'share_failed',
    timestamp: new Date().toISOString(),
    properties: payload,
  });
}
