export function success(data) {
    return { state: 'success', data };
}
export function failure(error, fallback) {
    return { state: 'failure', error, data: fallback };
}
export function offline(fallback, offlineQueueSize) {
    return { state: 'offline', data: fallback, offlineQueueSize };
}
export async function withOfflineFallback(task, fallback, options) {
    try {
        const data = await task();
        return success(data);
    }
    catch (error) {
        if (isOfflineError(error)) {
            const cached = await fallback();
            return offline(cached, options?.offlineQueueSize);
        }
        return failure(error.message);
    }
}
export function isOfflineError(error) {
    return (typeof navigator !== 'undefined' &&
        'onLine' in navigator &&
        navigator.onLine === false) ||
        (error instanceof Error && /offline|network/i.test(error.message));
}
//# sourceMappingURL=state.js.map