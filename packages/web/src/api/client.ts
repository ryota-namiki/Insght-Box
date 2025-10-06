import type {
  AnalyticsCardOutputs,
  AnalyticsPersonalOutputs,
  BoardHomeOutputs,
  CardDetailOutputs,
  CardSummary as CoreCardSummary,
  EventMeta,
  MarketplaceOutputs,
  SettingsRolesInputs,
  ShareModalInputs,
  ShareModalOutputs,
  Tag,
  TemplateModalInputs,
  TemplateModalOutputs,
} from '@insight-box/core';

// Laravel APIに合わせた拡張型
export interface CardSummary extends CoreCardSummary {
  company?: string;
  eventName?: string;
  authorName?: string;
}

export interface CardDetailResponse {
  id: string;
  summary: CardSummary;
  detail: any;
  reactions: any;
}

interface CreateCardPayload {
  title: string;
  companyName?: string;
  eventId: string;
  authorId?: string;
  ocrText?: string;
  tags: Tag[];
  memo?: string;
  documentId?: string;
  cameraImage?: string;
}

type ApiError = { message: string };

async function fetchJson<T>(input: RequestInfo, init?: RequestInit): Promise<T> {
  const response = await fetch(input, init);
  if (!response.ok) {
    let errorMessage = response.statusText;
    try {
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        const errorJson = (await response.json()) as ApiError;
        errorMessage = errorJson.message ?? errorMessage;
      } else {
        const text = await response.text();
        errorMessage = `HTTP ${response.status}: ${text.substring(0, 100)}`;
      }
    } catch (_err) {
      errorMessage = `HTTP ${response.status}: ${response.statusText}`;
    }
    throw new Error(errorMessage);
  }
  
  const contentType = response.headers.get('content-type');
  if (contentType && contentType.includes('application/json')) {
    return response.json() as Promise<T>;
  } else {
    const text = await response.text();
    throw new Error(`Expected JSON response but got: ${text.substring(0, 100)}`);
  }
}

export function getEvents(): Promise<EventMeta[]> {
  return fetchJson<{ events: EventMeta[] }>('/api/events').then((res) => res.events);
}

export function createEvent(eventData: {
  name: string;
  startDate: string;
  endDate: string;
  location?: string;
}): Promise<{ event: EventMeta }> {
  return fetchJson<{ event: EventMeta }>('/api/events', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(eventData),
  });
}

export function updateEvent(eventId: string, eventData: {
  name: string;
  startDate: string;
  endDate: string;
  location?: string;
}): Promise<{ event: EventMeta }> {
  return fetchJson<{ event: EventMeta }>(`/api/events/${eventId}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(eventData),
  });
}

export async function deleteEvent(eventId: string): Promise<void> {
  const response = await fetch(`/api/events/${eventId}`, {
    method: 'DELETE',
  });
  if (!response.ok) {
    throw new Error(`Failed to delete event: ${response.statusText}`);
  }
}

export function getCards(): Promise<CardSummary[]> {
  return fetchJson<{ cards: CardSummary[] }>('/api/cards').then((res) => res.cards);
}

export function createCard(payload: CreateCardPayload): Promise<{ card: CardDetailResponse; summary: CardSummary }> {
  return fetchJson<{ card: CardDetailResponse; summary: CardSummary }>('/api/cards', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
}

export function getCardDetail(cardId: string): Promise<CardDetailOutputs> {
  return fetchJson<CardDetailOutputs>(`/api/cards/${cardId}`);
}

export function getBoard(params?: { tagIds?: string[]; eventIds?: string[] }): Promise<CardSummary[]> {
  const query = new URLSearchParams();
  if (params?.tagIds?.length) query.set('tagIds', params.tagIds.join(','));
  if (params?.eventIds?.length) query.set('eventIds', params.eventIds.join(','));
  const suffix = query.toString() ? `?${query.toString()}` : '';
  return fetchJson<CardSummary[]>(`/api/board${suffix}`);
}

export function applyTemplate(inputs: TemplateModalInputs): Promise<TemplateModalOutputs> {
  return fetchJson<TemplateModalOutputs>('/api/templates/apply', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(inputs),
  });
}

export function getMarketplace(): Promise<MarketplaceOutputs> {
  return fetchJson<MarketplaceOutputs>('/api/marketplace');
}

export function shareCard(inputs: ShareModalInputs): Promise<ShareModalOutputs> {
  return fetchJson<ShareModalOutputs>('/api/share', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(inputs),
  });
}

export function getPersonalAnalytics(): Promise<AnalyticsPersonalOutputs> {
  return fetchJson<AnalyticsPersonalOutputs>('/api/analytics/personal');
}

export function getCardAnalytics(cardId: string): Promise<AnalyticsCardOutputs> {
  return fetchJson<AnalyticsCardOutputs>(`/api/analytics/cards/${cardId}`);
}

export function getSettings(): Promise<SettingsRolesInputs> {
  return fetchJson<{ settings: SettingsRolesInputs }>('/api/settings/roles').then((res) => res.settings);
}

export function updateSettings(inputs: SettingsRolesInputs): Promise<void> {
  return fetchJson('/api/settings/roles', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(inputs),
  }).then(() => undefined);
}

// カード編集機能
export function updateCard(cardId: string, payload: Partial<CreateCardPayload>): Promise<{ card: CardDetailResponse; summary: CardSummary }> {
  return fetchJson<CardDetailResponse>(`/api/cards/${cardId}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  }).then(cardData => ({ card: cardData, summary: cardData.summary }));
}

// カード削除機能
export async function deleteCard(cardId: string): Promise<void> {
  const response = await fetch(`/api/cards/${cardId}`, {
    method: 'DELETE',
  });

  if (!response.ok) {
    let errorMessage = response.statusText || 'Failed to delete card';
    try {
      const errorJson = (await response.json()) as ApiError;
      errorMessage = errorJson.message ?? errorMessage;
    } catch (_err) {
      // ignore JSON parse errors for empty responses
    }
    throw new Error(errorMessage);
  }

  return undefined;
}




// 統一REST API v1
export interface DocumentSource {
  type: 'file' | 'url';
  url?: string;
  file?: File;
  filename?: string;
  size?: number;
  mime_type?: string;
}

export interface DocumentMeta {
  expo?: string;
  booth?: string;
  event_id?: string;
  captured_at?: string;
  device?: string;
}

export interface Document {
  id: string;
  source: DocumentSource;
  meta: DocumentMeta;
  tasks: string[];
  status: 'queued' | 'processing' | 'completed' | 'failed';
  created_at: string;
  updated_at: string;
  artifacts: {
    text?: string;
    entities?: any;
    pages?: any[];
    original?: string;
  };
}

export interface Job {
  id: string;
  document_id: string;
  status: 'queued' | 'processing' | 'succeeded' | 'failed';
  progress: number;
  tasks: string[];
  created_at: string;
  started_at?: string;
  completed_at?: string;
  error?: string;
  artifacts: Array<{
    type: string;
    url: string;
    metadata?: any;
  }>;
}

// ドキュメント作成（統一API - Laravel対応）
export async function createDocument(source: DocumentSource, tasks: string[], meta: DocumentMeta, options?: any) {
  // Laravel APIに合わせてFormDataで送信
  const formData = new FormData();
  
  if (source.type === 'url' && source.url) {
    formData.append('url', source.url);
  } else if (source.type === 'file' && source.file) {
    formData.append('file', source.file);
  }
  
  formData.append('lang', 'jpn+eng');

  return fetchJson<{
    document_id: string;
    job_id: string;
  }>('/api/v1/documents', {
    method: 'POST',
    body: formData,
  });
}

// ファイルアップロード用のドキュメント作成（Laravel API対応）
export async function createDocumentFromFile(file: File, tasks: string[], meta: DocumentMeta, options?: any) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('lang', 'jpn+eng'); // Laravel API のパラメータ

  return fetchJson<{
    document_id: string;
    job_id: string;
    status: string;
  }>('/api/v1/documents', {
    method: 'POST',
    body: formData,
  });
}

// ジョブ取得
export async function getJob(jobId: string) {
  return fetchJson<Job>(`/api/v1/jobs/${jobId}`);
}

// ドキュメント取得
export async function getDocumentV1(documentId: string) {
  return fetchJson<Document>(`/api/v1/documents/${documentId}`);
}

// ドキュメントのテキスト取得
export async function getDocumentText(documentId: string) {
  return fetchJson<{ text: string }>(`/api/v1/documents/${documentId}/text`);
}

// ドキュメントメタデータ取得
export async function getDocumentMetadata(documentId: string) {
  return fetchJson<{ metadata: { title: string; description: string }; url: string | null }>(`/api/v1/documents/${documentId}/metadata`);
}

// ドキュメントのエンティティ取得
export async function getDocumentEntities(documentId: string) {
  return fetchJson<{ entities: any }>(`/api/v1/documents/${documentId}/entities`);
}

// 検索（v1）
export async function searchDocumentsV1(query?: string, filters?: string[]) {
  const params = new URLSearchParams();
  if (query) params.append('q', query);
  if (filters) filters.forEach(filter => params.append('filters', filter));
  
  return fetchJson<{
    results: Document[];
    total: number;
  }>(`/api/v1/search?${params.toString()}`);
}

// ドキュメント再処理
export async function reprocessDocument(documentId: string, tasks?: string[], options?: any) {
  return fetchJson<{
    job_id: string;
    status: string;
  }>(`/api/v1/documents/${documentId}/reprocess`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ tasks, options }),
  });
}

// カードの位置情報更新
export async function updateCardPosition(cardId: string, x: number, y: number) {
  return fetchJson<{ success: boolean }>(`/api/cards/${cardId}/position`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ x, y }),
  });
}

