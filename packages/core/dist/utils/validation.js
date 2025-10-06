import { LIMITS } from '../domain/common';
const PATTERNS = {
    id: /^[a-zA-Z0-9_-]{6,64}$/,
    uuid: /^[0-9a-fA-F-]{36}$/,
    isoDate: /^\d{4}-\d{2}-\d{2}$/,
    isoDateTime: /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/,
    url: /^(https?):\/\/[^\s]+$/,
    email: /^[^@\s]+@[^@\s]+\.[^@\s]+$/,
    tag: /^[A-Za-z0-9ぁ-んァ-ヶ一-龯ー・‐\-\s]{1,30}$/,
    title: /^[^\s].{0,119}$/,
    comment: /^.{1,500}$/,
    fileExt: /\.(?:jpe?g|png|pdf)$/i,
};
export function guard(condition, path, message, value) {
    return condition ? [] : [{ path, message, value }];
}
export function validateUploadHomeInputs(input) {
    const issues = [];
    if (input.searchQuery) {
        issues.push(...guard(input.searchQuery.length <= LIMITS.titleMax, 'searchQuery', '検索キーワードは120字以内', input.searchQuery), ...guard(/^.{0,120}$/.test(input.searchQuery), 'searchQuery', '検索キーワードが不正です', input.searchQuery));
    }
    return issues;
}
export function validateUploadReviewInputs(input) {
    const issues = [];
    issues.push(...guard(Boolean(input.files?.length), 'files', 'ファイルは1件以上必要です'), ...guard(input.title.length > 0, 'title', 'タイトルは必須です'), ...guard(input.title.length <= LIMITS.titleMax, 'title', 'タイトルは120字以内'), ...guard(PATTERNS.title.test(input.title), 'title', 'タイトルの形式が不正です', input.title), ...guard(Boolean(input.eventId), 'eventId', 'イベントは必須です'), ...guard(input.tags.length <= LIMITS.tagsMaxPerCard, 'tags', 'タグは20件以内'));
    for (const file of input.files) {
        issues.push(...guard(file.bytes > 0, `files.${file.id}.bytes`, 'ファイルサイズが不正です', file.bytes), ...guard(file.bytes <= LIMITS.fileSizeMbSingle * 1024 * 1024, `files.${file.id}.bytes`, 'ファイルサイズは20MB以内', file.bytes), ...guard(PATTERNS.fileExt.test(file.filename), `files.${file.id}.filename`, '拡張子はJPEG/PNG/PDFのみ', file.filename));
    }
    const totalBytes = input.files.reduce((sum, file) => sum + file.bytes, 0);
    issues.push(...guard(totalBytes <= LIMITS.fileSizeMbTotal * 1024 * 1024, 'files', '合計ファイルサイズは200MB以内', totalBytes));
    return issues;
}
export function validateBatchImportInputs(input) {
    const issues = [];
    const totalBytes = input.files.reduce((sum, file) => sum + file.bytes, 0);
    issues.push(...guard(input.files.length > 0, 'files', 'ファイルを選択してください'), ...guard(totalBytes <= LIMITS.fileSizeMbTotal * 1024 * 1024, 'files', '画像合計は200MB以内'), ...guard(!input.eventId || input.files.length <= LIMITS.eventUploadMax, 'files', `1イベント${LIMITS.eventUploadMax}件まで`));
    return issues;
}
export function validateBatchImportOutputs(output) {
    const issues = [];
    issues.push(...guard(Array.isArray(output.previews), 'previews', 'プレビューが不正です', output.previews));
    return issues;
}
export function validateCardDetail(inputs, outputs) {
    const issues = [];
    issues.push(...guard(Boolean(inputs.cardId), 'cardId', 'カードIDが必要です'));
    issues.push(...guard(outputs.header.title.length > 0, 'header.title', 'タイトルがありません'));
    issues.push(...guard(outputs.body.ocr.text.length <= LIMITS.ocrTextMax, 'body.ocr.text', 'OCR結果は10,000字以内'));
    return issues;
}
export function validateMarketplaceInputs(inputs) {
    const issues = [];
    issues.push(...guard(['following', 'trending', 'new', 'event'].includes(inputs.tab), 'tab', 'タブ指定が不正です', inputs.tab));
    issues.push(...guard(['trending', 'latest'].includes(inputs.sort), 'sort', 'ソート指定が不正です', inputs.sort));
    return issues;
}
export function validateShareInputs(inputs) {
    const issues = [];
    const todayIso = new Date().toISOString().slice(0, 10);
    issues.push(...guard(Boolean(inputs.cardId), 'cardId', 'カードIDが必要です'), ...guard(['team', 'org', 'link'].includes(inputs.scope), 'scope', '共有範囲が不正です', inputs.scope), ...guard(inputs.expiresAt >= todayIso, 'expiresAt', '有効期限が過去日です', inputs.expiresAt));
    if (inputs.slackTargets) {
        for (const target of inputs.slackTargets) {
            issues.push(...guard(Boolean(target.workspaceId), 'slackTargets.workspaceId', 'Slackワークスペースが必要です'));
        }
    }
    return issues;
}
export function assertValid(issues) {
    if (issues.length) {
        const error = new Error('Validation failed');
        error.issues = issues;
        throw error;
    }
}
//# sourceMappingURL=validation.js.map