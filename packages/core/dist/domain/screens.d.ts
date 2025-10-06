import { CardStatus, CardSummary, DepartmentRatio, EventMeta, FileDescriptor, OcrResult, Priority, ReactionSummary, ShareScope, SortOrder, Tag, TemplateType, UserMeta, ViewState } from './common';
type IsoDate = string;
type IsoDateTime = string;
export interface UploadHomeInputs {
    searchQuery?: string;
    actionUpload?: boolean;
}
export interface UploadHomeOutputs {
    recentCards: Array<Pick<CardSummary, 'id' | 'title' | 'status' | 'thumbnailUrl'>>;
    uploadQueue: UploadQueueItem[];
    state: ViewState;
}
export interface UploadQueueItem {
    localId: string;
    filename: string;
    bytes: number;
    status: CardStatus;
    progress: number;
    retryCount: number;
    eventId?: string;
    tags?: Tag[];
    error?: string;
}
export interface UploadReviewInputs {
    files: FileDescriptor[];
    title: string;
    memo?: string;
    assignee?: UserMeta;
    priority: Priority;
    tags: Tag[];
    eventId: string;
}
export interface UploadReviewOutputs {
    ocr: OcrResult;
    tagCandidates: TagSuggestion[];
    cardId?: string;
    state: ViewState;
    snackbarMessage?: string;
}
export interface TagSuggestion {
    tag: Tag;
    confidence: number;
}
export interface BatchImportInputs {
    files: FileDescriptor[];
    splitStrategy: 'file' | 'page';
    sharedTags: Tag[];
    eventId?: string;
}
export interface BatchImportOutputs {
    previews: BatchImportPreview[];
    state: ViewState;
}
export interface BatchImportPreview {
    fileId: string;
    filename: string;
    pages: number;
    generatedCards: number;
    blocked?: boolean;
    reason?: string;
}
export interface TagApprovalInputs {
    cards: TagApprovalItem[];
}
export interface TagApprovalItem {
    cardId: string;
    suggestions: TagSuggestion[];
}
export interface EventLinkingInputs {
    cardId: string;
    currentEventId?: string;
    selectableEvents: EventMeta[];
}
export interface BoardHomeInputs {
    query?: string;
    tagIds?: string[];
    eventIds?: string[];
    ownerIds?: string[];
    from?: IsoDate;
    to?: IsoDate;
}
export interface BoardHomeOutputs {
    canvas: BoardCanvas;
    relationGraph: RelationGraph;
    state: ViewState;
    templatesCtaVisible: boolean;
}
export interface BoardCanvas {
    cards: CanvasCard[];
    groups: CanvasGroup[];
}
export interface CanvasCard extends CardSummary {
    position: {
        x: number;
        y: number;
    };
    color?: string;
    memo?: string;
}
export interface CanvasGroup {
    id: string;
    name: string;
    cardIds: string[];
    color?: string;
}
export interface RelationGraph {
    nodes: GraphNode[];
    edges: GraphEdge[];
    density?: number;
}
export interface GraphNode {
    cardId: string;
    degree: number;
}
export interface GraphEdge {
    from: string;
    to: string;
    weight: number;
}
export interface TemplateModalInputs {
    templateType: TemplateType;
    slots: TemplateSlot[];
}
export interface TemplateSlot {
    id: string;
    label: string;
    cardIds: string[];
    summary?: string;
}
export interface TemplateModalOutputs {
    slotSummaries: TemplateSlot[];
    synthesisSummary: string;
    state: ViewState;
}
export interface CardDetailInputs {
    cardId: string;
}
export interface CardDetailOutputs {
    header: CardDetailHeader;
    body: CardDetailBody;
    sidebar: CardDetailSidebar;
    state: ViewState;
}
export interface CardDetailHeader {
    title: string;
    companyName?: string;
    event?: EventMeta;
    author: UserMeta;
    createdAt: IsoDateTime;
    updatedAt: IsoDateTime;
    priority: Priority;
}
export interface CardDetailBody {
    ocr: OcrResult;
    sourceFiles: FileDescriptor[];
    highlights: string[];
    tags: Tag[];
    webClipMetadata?: WebClipMetadata;
}
export interface WebClipMetadata {
    appleWebApp: WebClipAppleWebApp;
    manifest: WebClipManifest;
    configProfile: Record<string, unknown>;
    configurationProfile?: Record<string, unknown>;
}
export interface WebClipAppleWebApp {
    capable: boolean;
    statusBarStyle: string;
    title: string;
    startupImage: string;
    icons: Array<{
        sizes: string;
        src: string;
        type: string;
    }>;
}
export interface WebClipManifest {
    name: string;
    short_name: string;
    description: string;
    start_url: string;
    display: string;
    orientation: string;
    theme_color: string;
    background_color: string;
    icons: Array<{
        src: string;
        sizes: string;
        type: string;
    }>;
}
export interface CardDetailSidebar {
    relatedCards: Array<Pick<CardSummary, 'id' | 'title' | 'thumbnailUrl'>>;
    collections: CollectionAssignment[];
    history: HistoryEntry[];
    reactions: ReactionSummary;
}
export interface CollectionAssignment {
    collectionId: string;
    name: string;
}
export interface HistoryEntry {
    action: string;
    actor: UserMeta;
    at: IsoDateTime;
    details?: string;
}
export interface MarketplaceInputs {
    tab: 'following' | 'trending' | 'new' | 'event';
    sort: SortOrder;
    eventId?: string;
}
export interface MarketplaceOutputs {
    cards: MarketplaceCard[];
    state: ViewState;
}
export interface MarketplaceCard {
    summary: CardSummary;
    reactions: ReactionSummary;
    pinned: boolean;
}
export interface ShareModalInputs {
    cardId: string;
    scope: ShareScope;
    expiresAt: IsoDate;
    slackTargets?: SlackTarget[];
}
export interface SlackTarget {
    workspaceId: string;
    channelId?: string;
    memberId?: string;
}
export interface ShareModalOutputs {
    shareUrl: string;
    postedToSlack: boolean;
    state: ViewState;
    errors?: string[];
}
export interface AnalyticsPersonalInputs {
    range: {
        from: IsoDate;
        to: IsoDate;
    };
}
export interface AnalyticsPersonalOutputs {
    kpis: ReactionSummary;
    timeseries: AnalyticsTimeseries[];
    state: ViewState;
}
export interface AnalyticsTimeseries {
    date: IsoDate;
    views: number;
    comments: number;
    likes: number;
}
export interface AnalyticsCardInputs {
    cardId: string;
    range: {
        from: IsoDate;
        to: IsoDate;
    };
}
export interface AnalyticsCardOutputs {
    kpis: ReactionSummary;
    timeseries: AnalyticsTimeseries[];
    audienceDistribution: DepartmentRatio[];
    state: ViewState;
}
export interface SettingsRolesInputs {
    defaultScope: Exclude<ShareScope, 'link'>;
    eventUploadMax: number;
}
export interface SettingsRolesOutputs {
    applied: boolean;
    state: ViewState;
}
export {};
//# sourceMappingURL=screens.d.ts.map