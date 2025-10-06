import { ViewState } from '../domain/common';

export interface ViewModel<T> {
  state: ViewState;
  data?: T;
  error?: string;
  offlineQueueSize?: number;
}

export function success<T>(data: T): ViewModel<T> {
  return { state: 'success', data };
}

export function failure<T>(error: string, fallback?: Partial<T>): ViewModel<T> {
  return { state: 'failure', error, data: fallback as T };
}

export function offline<T>(fallback?: Partial<T>, offlineQueueSize?: number): ViewModel<T> {
  return { state: 'offline', data: fallback as T, offlineQueueSize };
}

export type AsyncStateHandler<I, O> = (input: I) => Promise<ViewModel<O>>;

export async function withOfflineFallback<I, O>(
  task: () => Promise<O>,
  fallback: () => Promise<Partial<O> | undefined>,
  options?: { offlineQueueSize?: number },
): Promise<ViewModel<O>> {
  try {
    const data = await task();
    return success(data);
  } catch (error) {
    if (isOfflineError(error)) {
      const cached = await fallback();
      return offline(cached, options?.offlineQueueSize);
    }
    return failure((error as Error).message);
  }
}

export function isOfflineError(error: unknown): boolean {
  return (
    typeof navigator !== 'undefined' &&
    'onLine' in navigator &&
    navigator.onLine === false
  ) ||
    (error instanceof Error && /offline|network/i.test(error.message));
}
