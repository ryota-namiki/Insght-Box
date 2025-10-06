export type Priority = 1 | 2 | 3;
export type ShareScope = 'team' | 'org' | 'link';
export type TemplateType = '4P' | 'SWOT';
export type CardStatus = 'pending' | 'processing' | 'ready' | 'failed';
export type SortOrder = 'trending' | 'latest';

export type ViewState = 'success' | 'failure' | 'offline';

export const LIMITS = {
  fileSizeMbSingle: 20,
  fileSizeMbTotal: 200,
  eventUploadMax: 20,
  tagsMaxPerCard: 20,
  ocrTextMax: 10_000,
  titleMax: 120,
  memoMax: 1_000,
} as const;

export interface Identifiable {
  id: string;
}

export interface Timestamped {
  createdAt: string; // ISO datetime
  updatedAt: string; // ISO datetime
}

export interface Tag {
  id: string;
  label: string;
  confidence?: number;
}

export interface EventMeta {
  id: string;
  name: string;
  startDate: string; // ISO date
  endDate: string; // ISO date
  location?: string;
}

export interface UserMeta {
  id: string;
  name: string;
  avatarUrl?: string;
  role: 'visitor' | 'member' | 'admin';
}

export interface CardSummary {
  id: string;
  title: string;
  status: CardStatus;
  thumbnailUrl?: string;
  eventId?: string;
  tags?: Tag[];
  createdAt: string;
  createdBy: UserMeta;
}

export interface FileDescriptor {
  id: string;
  filename: string;
  mimeType: string;
  bytes: number;
  pages?: number;
  checksum?: string;
}

export interface OcrResult {
  text: string;
  revisedBy?: string;
  updatedAt: string;
  latencyMs?: number;
}

export interface ReactionSummary {
  views: number;
  comments: number;
  likes: number;
}

export interface DepartmentRatio {
  department: string;
  ratio: number;
}
