export type Priority = 1 | 2 | 3;
export type ShareScope = 'team' | 'org' | 'link';
export type TemplateType = '4P' | 'SWOT';
export type CardStatus = 'pending' | 'processing' | 'ready' | 'failed';
export type SortOrder = 'trending' | 'latest';
export type ViewState = 'success' | 'failure' | 'offline';
export declare const LIMITS: {
    readonly fileSizeMbSingle: 20;
    readonly fileSizeMbTotal: 200;
    readonly eventUploadMax: 20;
    readonly tagsMaxPerCard: 20;
    readonly ocrTextMax: 10000;
    readonly titleMax: 120;
    readonly memoMax: 1000;
};
export interface Identifiable {
    id: string;
}
export interface Timestamped {
    createdAt: string;
    updatedAt: string;
}
export interface Tag {
    id: string;
    label: string;
    confidence?: number;
}
export interface EventMeta {
    id: string;
    name: string;
    startDate: string;
    endDate: string;
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
//# sourceMappingURL=common.d.ts.map