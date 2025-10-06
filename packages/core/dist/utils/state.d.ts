import { ViewState } from '../domain/common';
export interface ViewModel<T> {
    state: ViewState;
    data?: T;
    error?: string;
    offlineQueueSize?: number;
}
export declare function success<T>(data: T): ViewModel<T>;
export declare function failure<T>(error: string, fallback?: Partial<T>): ViewModel<T>;
export declare function offline<T>(fallback?: Partial<T>, offlineQueueSize?: number): ViewModel<T>;
export type AsyncStateHandler<I, O> = (input: I) => Promise<ViewModel<O>>;
export declare function withOfflineFallback<I, O>(task: () => Promise<O>, fallback: () => Promise<Partial<O> | undefined>, options?: {
    offlineQueueSize?: number;
}): Promise<ViewModel<O>>;
export declare function isOfflineError(error: unknown): boolean;
//# sourceMappingURL=state.d.ts.map