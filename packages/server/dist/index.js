import express from 'express';
import cors from 'cors';
import multer from 'multer';
import { v4 as uuid } from 'uuid';
import Tesseract from 'tesseract.js';
import fs from 'fs';
import path from 'path';
import sharp from 'sharp';
import { PDFDocument } from 'pdf-lib';
import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf.js';
import { createCanvas } from 'canvas';
import { LIMITS, createEventSimilarity, createTagSimilarity, combineSimilarities, BoardService, } from '@insight-box/core';
const app = express();
app.use(cors());
app.use(express.json({ limit: '10mb' }));
const UPLOADS_DIR = path.join(process.cwd(), 'uploads');
const TEMP_DIR = path.join(UPLOADS_DIR, 'tmp');
const WEB_DIST_DIR = path.join(process.cwd(), 'packages', 'web', 'dist');
function ensureDirectoryExists(dirPath) {
    if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
    }
}
ensureDirectoryExists(UPLOADS_DIR);
ensureDirectoryExists(TEMP_DIR);
if (fs.existsSync(WEB_DIST_DIR)) {
    app.use('/namiki/insight-box', express.static(WEB_DIST_DIR));
    app.get('/namiki/insight-box/*', (_req, res) => {
        res.sendFile(path.join(WEB_DIST_DIR, 'index.html'));
    });
}
function createTempFilePath(prefix, extension = '.png') {
    const uniqueSuffix = `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    return path.join(TEMP_DIR, `${prefix}_${uniqueSuffix}${extension}`);
}
function cleanupTempFiles(paths) {
    for (const filePath of paths) {
        if (!filePath || filePath === '' || !fs.existsSync(filePath)) {
            continue;
        }
        try {
            fs.unlinkSync(filePath);
        }
        catch (error) {
            console.warn('一時ファイル削除時にエラー:', error);
        }
    }
}
function updateJobProgress(jobId, progress) {
    if (!jobId || !global.jobs) {
        return;
    }
    const job = global.jobs.get(jobId);
    if (!job) {
        return;
    }
    const currentProgress = job.progress ?? 0;
    const normalized = Math.max(currentProgress, Math.min(Math.round(progress), 99));
    if (normalized !== currentProgress) {
        job.progress = normalized;
        global.jobs.set(jobId, job);
    }
}
async function createRotatedImageCopy(filePath, angle) {
    const rotatedPath = createTempFilePath(`rot${angle}`);
    await sharp(filePath)
        .rotate(angle, { background: { r: 255, g: 255, b: 255, alpha: 1 } })
        .png({ quality: 100 })
        .toFile(rotatedPath);
    return rotatedPath;
}
// ファイルアップロード用の設定
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const uploadDir = path.join(process.cwd(), 'uploads');
        if (!fs.existsSync(uploadDir)) {
            fs.mkdirSync(uploadDir, { recursive: true });
        }
        cb(null, uploadDir);
    },
    filename: (req, file, cb) => {
        const uniqueName = `${Date.now()}-${Math.random().toString(36).slice(2)}-${file.originalname}`;
        cb(null, uniqueName);
    }
});
const upload = multer({
    storage,
    limits: { fileSize: 10 * 1024 * 1024 }, // 10MB制限
    fileFilter: (req, file, cb) => {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
        if (allowedTypes.includes(file.mimetype)) {
            cb(null, true);
        }
        else {
            cb(new Error('サポートされていないファイル形式です'));
        }
    }
});
// 静的ファイル配信
app.use('/uploads', express.static(path.join(process.cwd(), 'uploads')));
const PORT = process.env.PORT ?? 5178;
const users = [
    { id: 'user-ryo', name: 'Ryota Namiki', role: 'visitor' },
    { id: 'user-ami', name: 'Ami Sato', role: 'member' },
    { id: 'user-admin', name: 'Admin Imai', role: 'admin' },
];
// イベントストア（メモリ内）
const eventStore = new Map();
// 初期イベントデータ
const initialEvents = [
    {
        id: 'event-2025-tokyo',
        name: '展示会 2025 TOKYO',
        startDate: '2025-02-12',
        endDate: '2025-02-15',
        location: 'Tokyo Big Sight',
    },
    {
        id: 'event-medtech',
        name: 'MedTech Summit',
        startDate: '2025-03-05',
        endDate: '2025-03-07',
        location: 'Osaka',
    },
];
// 初期イベントの追加は loadEventsFromFile() で処理されます
// イベント一覧を取得する関数
function getEvents() {
    return Array.from(eventStore.values());
}
const sampleTags = [
    { id: '3d-printing', label: '3Dプリンティング' },
    { id: 'medical', label: '医療' },
    { id: 'robotics', label: 'ロボティクス' },
    { id: 'ai', label: 'AI' },
    { id: 'event2025', label: '展示会2025' },
];
function createCardRecord(params) {
    const nowIso = new Date().toISOString();
    const event = params.eventId ? getEvents().find((e) => e.id === params.eventId) : undefined;
    const author = users.find((u) => u.id === params.authorId) ?? users[0];
    const cardId = params.cardId ?? uuid();
    const detail = {
        header: {
            title: params.title,
            companyName: params.companyName,
            event,
            author,
            createdAt: nowIso,
            updatedAt: nowIso,
            priority: 2,
        },
        body: {
            ocr: {
                text: params.ocrText,
                updatedAt: nowIso,
            },
            sourceFiles: [
                {
                    id: `${cardId}-file`,
                    filename: `${cardId}.jpg`,
                    mimeType: 'image/jpeg',
                    bytes: 2048,
                },
            ],
            highlights: params.memo ? [params.memo] : [],
            tags: params.tags,
        },
        sidebar: {
            relatedCards: [],
            collections: [],
            history: [
                {
                    action: 'created',
                    actor: author,
                    at: nowIso,
                    details: 'カードを作成',
                },
            ],
            reactions: {
                views: Math.floor(Math.random() * 200) + 20,
                comments: Math.floor(Math.random() * 10),
                likes: Math.floor(Math.random() * 15),
            },
        },
        state: 'success',
    };
    const summary = {
        id: cardId,
        title: detail.header.title,
        status: 'ready',
        thumbnailUrl: detail.body.sourceFiles[0]?.filename ? `/assets/${detail.body.sourceFiles[0].filename}` : undefined,
        eventId: detail.header.event?.id,
        tags: detail.body.tags,
        createdAt: detail.header.createdAt,
        createdBy: detail.header.author,
    };
    const timeseries = Array.from({ length: 7 }, (_, idx) => {
        const date = new Date();
        date.setDate(date.getDate() - (6 - idx));
        return {
            date: date.toISOString().slice(0, 10),
            views: Math.floor(Math.random() * 40) + 10,
            comments: Math.floor(Math.random() * 5),
            likes: Math.floor(Math.random() * 6),
        };
    });
    const audience = [
        { department: '営業', ratio: 0.35 },
        { department: '企画', ratio: 0.25 },
        { department: '開発', ratio: 0.2 },
        { department: 'その他', ratio: 0.2 },
    ];
    return {
        id: cardId,
        detail,
        summary,
        reactions: detail.sidebar.reactions,
        timeseries,
        audience,
    };
}
// データディレクトリの設定
const DATA_DIR = path.join(process.cwd(), 'data');
const CARDS_FILE = path.join(DATA_DIR, 'cards.json');
const EVENTS_FILE = path.join(DATA_DIR, 'events.json');
// データディレクトリが存在しない場合は作成
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}
const cardStore = new Map();
// データの保存・読み込み関数
function saveCardsToFile() {
    try {
        const cardsData = Array.from(cardStore.entries());
        fs.writeFileSync(CARDS_FILE, JSON.stringify(cardsData, null, 2));
        console.log(`カードデータを保存しました: ${cardsData.length}件`);
    }
    catch (error) {
        console.error('カードデータの保存に失敗しました:', error);
    }
}
function loadCardsFromFile() {
    try {
        if (fs.existsSync(CARDS_FILE)) {
            const data = fs.readFileSync(CARDS_FILE, 'utf8');
            const cardsData = JSON.parse(data);
            cardStore.clear();
            cardsData.forEach(([id, record]) => {
                cardStore.set(id, record);
            });
            console.log(`カードデータを読み込みました: ${cardsData.length}件`);
        }
        else {
            console.log('カードデータファイルが存在しません。新規作成します。');
        }
    }
    catch (error) {
        console.error('カードデータの読み込みに失敗しました:', error);
    }
}
function saveEventsToFile() {
    try {
        const eventsData = Array.from(eventStore.entries());
        fs.writeFileSync(EVENTS_FILE, JSON.stringify(eventsData, null, 2));
        console.log(`イベントデータを保存しました: ${eventsData.length}件`);
    }
    catch (error) {
        console.error('イベントデータの保存に失敗しました:', error);
    }
}
function loadEventsFromFile() {
    try {
        if (fs.existsSync(EVENTS_FILE)) {
            const data = fs.readFileSync(EVENTS_FILE, 'utf8');
            const eventsData = JSON.parse(data);
            eventStore.clear();
            eventsData.forEach(([id, event]) => {
                eventStore.set(id, event);
            });
            console.log(`イベントデータを読み込みました: ${eventsData.length}件`);
        }
        else {
            // 初期イベントデータをストアに追加
            initialEvents.forEach(event => {
                eventStore.set(event.id, event);
            });
            saveEventsToFile();
            console.log('初期イベントデータを作成しました。');
        }
    }
    catch (error) {
        console.error('イベントデータの読み込みに失敗しました:', error);
    }
}
function seedData() {
    // 既存のデータがある場合は初期化しない
    if (cardStore.size > 0) {
        console.log('既存のカードデータが存在するため、初期データの作成をスキップします。');
        return;
    }
    const demoCards = [
        createCardRecord({
            title: '次世代3Dプリンタ',
            companyName: 'XYZ Additive',
            eventId: 'event-2025-tokyo',
            authorId: 'user-ryo',
            ocrText: '高精度で医療用パーツを短時間で製造可能。',
            tags: [sampleTags[0], sampleTags[1], sampleTags[4]],
            memo: 'サンプル展示あり',
        }),
        createCardRecord({
            title: 'AI診断プラットフォーム',
            companyName: 'MedScope',
            eventId: 'event-medtech',
            authorId: 'user-ami',
            ocrText: 'AIを活用した術前診断、臨床導入数120件。',
            tags: [sampleTags[1], sampleTags[3]],
            memo: '導入事例を要チェック',
        }),
        createCardRecord({
            title: '協働ロボット T-Assist',
            companyName: 'Techno Assist',
            eventId: 'event-2025-tokyo',
            authorId: 'user-admin',
            ocrText: '軽量で持ち運びが容易、現場での実証実験中。',
            tags: [sampleTags[2], sampleTags[4]],
        }),
    ];
    demoCards.forEach((record) => {
        cardStore.set(record.id, record);
    });
    // 初期データを保存
    saveCardsToFile();
    console.log('初期カードデータを作成しました。');
}
// サーバー起動時にデータを読み込み
loadEventsFromFile();
loadCardsFromFile();
seedData();
// テキストクリーニング関数（改行を保持）
// ポスト処理：ドメイン辞書と正規表現による拘束デコード
function applyPostProcessingCorrection(text) {
    let correctedText = text;
    // 郵便番号の正規化（例：123-4567 → 123-4567）
    correctedText = correctedText.replace(/(\d{3})\s*-\s*(\d{4})/g, '$1-$2');
    // 日付の正規化（例：2025/1/1 → 2025/01/01）
    correctedText = correctedText.replace(/(\d{4})\/(\d{1,2})\/(\d{1,2})/g, (match, year, month, day) => {
        return `${year}/${month.padStart(2, '0')}/${day.padStart(2, '0')}`;
    });
    // 金額の正規化（例：1,000円 → 1,000円）
    correctedText = correctedText.replace(/(\d{1,3}(?:,\d{3})*)\s*円/g, '$1円');
    // 電話番号の正規化（例：03-1234-5678 → 03-1234-5678）
    correctedText = correctedText.replace(/(\d{2,4})\s*-\s*(\d{4})\s*-\s*(\d{4})/g, '$1-$2-$3');
    // よくあるOCR誤りを修正（全角数字・英字を半角に）
    const commonCorrections = [
        { from: /[０-９]/g, to: (match) => String.fromCharCode(match.charCodeAt(0) - 0xFF10 + 0x30) },
        { from: /[Ａ-Ｚ]/g, to: (match) => String.fromCharCode(match.charCodeAt(0) - 0xFF21 + 0x41) },
        { from: /[ａ-ｚ]/g, to: (match) => String.fromCharCode(match.charCodeAt(0) - 0xFF41 + 0x61) },
    ];
    for (const correction of commonCorrections) {
        correctedText = correctedText.replace(correction.from, correction.to);
    }
    return correctedText;
}
// 文書構造を認識して適切な改行を保持する関数
function preserveDocumentStructure(text) {
    return text
        // 見出しパターンを認識（数字. で始まる行）
        .replace(/^(\s*)(\d+\.\s*[^\n]+)/gm, '$1$2\n')
        // サブ見出しパターンを認識（数字.数字 で始まる行）
        .replace(/^(\s*)(\d+\.\d+\s*[^\n]+)/gm, '$1$2\n')
        // 箇条書きパターンを認識（・、*、- で始まる行）
        .replace(/^(\s*)([・\*\-\u2022]\s*[^\n]+)/gm, '$1$2\n')
        // 詳細要件の箇条書き（* で始まる行）
        .replace(/^(\s*)(\*\s*[^\n]+)/gm, '$1$2\n')
        // 概要・詳細要件の区切りを認識
        .replace(/(概要|詳細要件)[:：]\s*/g, '$1:\n')
        // 段落の区切りを認識（句読点で終わる行の後）
        .replace(/([。！？])\s*([あ-んア-ン一-龯])/g, '$1\n$2');
}
function cleanExtractedTextWithLineBreaks(text) {
    // まずポスト処理を適用
    let cleanedText = applyPostProcessingCorrection(text);
    // 文書構造を保持
    cleanedText = preserveDocumentStructure(cleanedText);
    return cleanedText
        // タブをスペースに変換
        .replace(/\t/g, ' ')
        // 行内の複数の連続するスペースを1つに（改行は保持）
        .replace(/[ \t]+/g, ' ')
        // 行の先頭と末尾のスペースを削除
        .replace(/^[ \t]+|[ \t]+$/gm, '')
        // 完全に空の行を削除
        .replace(/^\s*$/gm, '')
        // 3つ以上の連続する改行を2つに制限
        .replace(/\n{3,}/g, '\n\n')
        // 句読点の前後のスペースを完全に削除
        .replace(/\s*([。、！？])\s*/g, '$1')
        // 括弧の前後のスペースを完全に削除
        .replace(/\s*([（）「」『』【】〈〉《》〔〕［］｛｝])\s*/g, '$1')
        // コロン、セミコロンの前後のスペースを調整
        .replace(/\s*([:;])\s*/g, '$1 ')
        // カンマの前後のスペースを調整
        .replace(/\s*,\s*/g, ', ')
        // ピリオドの前後のスペースを調整
        .replace(/\s*\.\s*/g, '. ')
        // 英数字と日本語の間の不要なスペースを削除
        .replace(/([a-zA-Z0-9])\s+([あ-んア-ン一-龯])/g, '$1$2')
        .replace(/([あ-んア-ン一-龯])\s+([a-zA-Z0-9])/g, '$1$2')
        // 日本語文字間の不要なスペースを削除
        .replace(/([あ-んア-ン一-龯])\s+([あ-んア-ン一-龯])/g, '$1$2')
        // 数字と日本語の間の不要なスペースを削除
        .replace(/([0-9])\s+([あ-んア-ン一-龯])/g, '$1$2')
        .replace(/([あ-んア-ン一-龯])\s+([0-9])/g, '$1$2')
        // 連続する句読点の間のスペースを削除
        .replace(/([。、！？])\s+([。、！？])/g, '$1$2')
        // 見出しの後の改行を調整
        .replace(/(\d+\.\d*[^\n]*)\n+/g, '$1\n')
        // 箇条書きの後の改行を調整
        .replace(/([・\*\-\u2022][^\n]*)\n+/g, '$1\n')
        // 最終的なトリム
        .trim();
}
// テキストクリーニング関数（従来版）
function cleanExtractedText(text) {
    return text
        // タブをスペースに変換
        .replace(/\t/g, ' ')
        // 複数の連続するスペースを1つに
        .replace(/\s+/g, ' ')
        // 行の先頭と末尾のスペースを削除
        .replace(/^\s+|\s+$/gm, '')
        // 空行を削除
        .replace(/^\s*$/gm, '')
        // 複数の連続する改行を2つに制限
        .replace(/\n\s*\n\s*\n+/g, '\n\n')
        // 句読点の前後のスペースを調整
        .replace(/\s+([。、！？])/g, '$1')
        .replace(/([。、！？])\s+/g, '$1 ')
        // 括弧の前後のスペースを調整
        .replace(/\s*([（）「」『』【】〈〉《》〔〕［］｛｝])\s*/g, '$1')
        // 英数字と日本語の間にスペースを追加（必要に応じて）
        .replace(/([a-zA-Z0-9])([あ-んア-ン一-龯])/g, '$1 $2')
        .replace(/([あ-んア-ン一-龯])([a-zA-Z0-9])/g, '$1 $2')
        // 最終的なトリム
        .trim();
}
// 画像前処理関数（OCR精度向上のため）
async function preprocessImageForOCR(filePath) {
    console.log('OCR用前処理: 基本処理開始', filePath);
    const processedPath = createTempFilePath('ocr');
    await sharp(filePath)
        .resize({
        height: 2000,
        fit: 'inside',
        withoutEnlargement: true,
    })
        .grayscale()
        .normalize()
        .median(1)
        .linear(1.1, -10)
        .sharpen({ sigma: 1, m1: 0.6, m2: 1.5, x1: 2, y2: 10 })
        .threshold(0, { grayscale: true })
        .extend({
        top: 16,
        bottom: 16,
        left: 16,
        right: 16,
        background: { r: 255, g: 255, b: 255, alpha: 1 },
    })
        .toFile(processedPath);
    console.log('OCR用前処理: 基本処理完了', processedPath);
    return processedPath;
}
// 文書構造認識のための前処理
async function preprocessForDocumentStructure(filePath) {
    console.log('OCR用前処理: 文書構造解析処理開始', filePath);
    const structurePath = createTempFilePath('structure');
    await sharp(filePath)
        .resize({
        height: 2200,
        fit: 'inside',
        withoutEnlargement: true,
    })
        .grayscale()
        .normalize()
        .median(2)
        .threshold(0, { grayscale: true })
        .sharpen({ sigma: 2, m1: 1, m2: 3, x1: 3, y2: 15 })
        .extend({
        top: 24,
        bottom: 24,
        left: 24,
        right: 24,
        background: { r: 255, g: 255, b: 255, alpha: 1 },
    })
        .toFile(structurePath);
    console.log('OCR用前処理: 文書構造解析処理完了', structurePath);
    return structurePath;
}
// OCR処理関数（文書構造認識強化版）
async function extractTextFromImage(filePath, jobId) {
    console.log('OCR処理開始:', filePath);
    let processedPath = filePath;
    let structurePath = filePath;
    const tempFiles = [];
    try {
        if (!fs.existsSync(filePath)) {
            console.error('ファイルが存在しません:', filePath);
            return '';
        }
        const { bestPath, cleanup: rotationCleanup } = await evaluateOrientationVariants(filePath, jobId);
        tempFiles.push(...rotationCleanup.filter(path => path !== bestPath));
        console.log('画像前処理開始');
        const [normalProcessed, structureProcessed] = await Promise.all([
            preprocessImageForOCR(bestPath),
            preprocessForDocumentStructure(bestPath)
        ]);
        processedPath = normalProcessed;
        structurePath = structureProcessed;
        tempFiles.push(processedPath, structurePath);
        const documentStructureModes = [
            { lang: 'jpn+jpn_vert+eng', psm: ['1', '3', '4', '6', '11'] },
            { lang: 'jpn_vert+eng', psm: ['5', '4'] }
        ];
        let bestResult = '';
        let bestConfidence = 0;
        for (const langMode of documentStructureModes) {
            for (const psmMode of langMode.psm) {
                try {
                    const { data } = await Tesseract.recognize(structurePath, langMode.lang, {
                        logger: tesseractLogger(jobId, 0, 60),
                        tessedit_pageseg_mode: psmMode,
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんがぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽゃゅょっー・、。！？（）「」『』【】〈〉《》〔〕［］｛｝',
                        tessedit_min_char_size: '4',
                        tessedit_max_char_size: '90',
                        preserve_interword_spaces: '1',
                    });
                    const confidence = data.confidence || 0;
                    if (confidence > bestConfidence) {
                        bestResult = data.text;
                        bestConfidence = confidence;
                    }
                }
                catch (psmError) {
                    console.warn(`${langMode.lang} PSM ${psmMode} でエラー:`, psmError);
                }
            }
        }
        if (bestConfidence < 65) {
            const layout = await detectLayoutBlocks(structurePath);
            console.log('レイアウト解析結果:', layout.blocks.length, 'ブロック');
            const layoutTexts = [];
            const sortedBlocks = layout.blocks
                .filter(block => block.width > 0 && block.height > 0)
                .sort((a, b) => a.top - b.top || a.left - b.left);
            for (const block of sortedBlocks) {
                try {
                    const blockPath = createTempFilePath('block');
                    tempFiles.push(blockPath);
                    await sharp(bestPath)
                        .extract({ left: block.left, top: block.top, width: block.width, height: block.height })
                        .extend({
                        top: 4,
                        bottom: 4,
                        left: 4,
                        right: 4,
                        background: { r: 255, g: 255, b: 255, alpha: 1 },
                    })
                        .toFile(blockPath);
                    const { data } = await recognizeText(blockPath, 'jpn+jpn_vert+eng', {
                        logger: tesseractLogger(jobId, 45, 45),
                        tessedit_pageseg_mode: block.psm,
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんがぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽゃゅょっー・、。！？（）「」『』【】〈〉《》〔〕［］｛｝',
                        tessedit_min_char_size: '4',
                        tessedit_max_char_size: '90',
                        preserve_interword_spaces: '1',
                    });
                    const text = data.text?.trim();
                    if (text && text.length > 0) {
                        layoutTexts.push(text);
                    }
                }
                catch (blockError) {
                    console.warn('ブロックOCRでエラー:', blockError);
                }
            }
            if (layoutTexts.length > 0) {
                const combined = layoutTexts.join('\n\n');
                const combinedConfidence = Math.min(98, Math.round(bestConfidence + layoutTexts.length * 3));
                if (combinedConfidence > bestConfidence) {
                    bestResult = combined;
                    bestConfidence = combinedConfidence;
                }
            }
        }
        if (bestConfidence < 50) {
            try {
                console.log('フォールバックOCR処理開始');
                const { data } = await Tesseract.recognize(processedPath, 'jpn+jpn_vert+eng', {
                    logger: tesseractLogger(jobId, 60, 40),
                    tessedit_pageseg_mode: '6',
                    tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんがぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽゃゅょっー・、。！？（）「」『』【】〈〉《》〔〕［］｛｝',
                    tessedit_min_char_size: '6',
                    tessedit_max_char_size: '90',
                    preserve_interword_spaces: '1',
                });
                console.log('フォールバックOCR処理完了:', data.text.length, '文字');
                if ((data.confidence || 0) > bestConfidence) {
                    bestResult = data.text;
                }
            }
            catch (fallbackError) {
                console.warn('フォールバックOCRでエラー:', fallbackError);
            }
        }
        const finalText = cleanExtractedTextWithLineBreaks(bestResult || 'テキストを認識できませんでした');
        console.log('OCR処理完了:', finalText.length, '文字');
        return finalText;
    }
    catch (error) {
        console.error('OCR処理エラー:', error);
        return 'OCR処理中にエラーが発生しました';
    }
    finally {
        cleanupTempFiles(tempFiles);
    }
}
// PDFをOCRで処理する関数（フォールバック用）
async function extractTextFromPDFWithOCR(filePath, jobId) {
    const pdfBytes = fs.readFileSync(filePath);
    const loadingTask = pdfjsLib.getDocument({ data: pdfBytes, useSystemFonts: true });
    const reader = await loadingTask.promise;
    return await ocrPdfPages(reader, jobId, Math.min(reader.numPages, 10));
}
// PDFの内部構造からテキストを抽出する関数（pdfjs-dist使用）
async function extractTextFromPDFStructure(pdfBytes) {
    try {
        // Canvas polyfillを設定
        const canvas = createCanvas(1, 1);
        const ctx = canvas.getContext('2d');
        // pdfjs-distを使用してPDFからテキストを抽出
        const loadingTask = pdfjsLib.getDocument({
            data: pdfBytes,
            useSystemFonts: true,
            verbosity: 0, // ログを抑制
            canvasFactory: {
                create: () => canvas,
                reset: () => { },
                destroy: () => { }
            }
        });
        const pdf = await loadingTask.promise;
        let extractedText = '';
        for (let pageNum = 1; pageNum <= Math.min(pdf.numPages, 20); pageNum++) {
            try {
                const page = await pdf.getPage(pageNum);
                const textContent = await page.getTextContent();
                // テキストアイテムを整理して抽出
                const pageText = textContent.items
                    .map((item) => {
                    if (item.str && item.str.trim()) {
                        return item.str.trim();
                    }
                    return '';
                })
                    .filter(text => text.length > 0)
                    .join(' ');
                if (pageText) {
                    extractedText += `=== ページ ${pageNum} ===\n${pageText}\n\n`;
                }
            }
            catch (pageError) {
                console.warn(`ページ ${pageNum}の処理エラー:`, pageError);
            }
        }
        return extractedText.trim();
    }
    catch (error) {
        console.warn('PDF構造解析エラー:', error);
        return '';
    }
}
// テキストファイル読み取り関数
function extractTextFromTextFile(filePath) {
    try {
        const text = fs.readFileSync(filePath, 'utf-8');
        return cleanExtractedTextWithLineBreaks(text);
    }
    catch (error) {
        console.error('テキストファイル読み取りエラー:', error);
        return 'テキストファイルの読み取りに失敗しました';
    }
}
// ファイルタイプに応じたテキスト抽出
async function extractTextFromFile(filePath, mimeType, options = {}) {
    if (mimeType.startsWith('image/')) {
        return await extractTextFromImage(filePath);
    }
    else if (mimeType === 'application/pdf') {
        return await extractTextFromPDF(filePath, options.enableOCR, undefined);
    }
    else if (mimeType === 'text/plain') {
        return extractTextFromTextFile(filePath);
    }
    else {
        return 'サポートされていないファイル形式です';
    }
}
function getSummaries() {
    return Array.from(cardStore.values()).map((record) => record.summary);
}
function getMarketplaceCards() {
    return Array.from(cardStore.values()).map((record) => ({
        summary: record.summary,
        reactions: record.reactions,
        pinned: false,
    }));
}
const boardService = new BoardService(async () => getSummaries(), combineSimilarities(createTagSimilarity(), createEventSimilarity(0.6)));
app.get('/api/health', (_req, res) => {
    res.json({ status: 'ok' });
});
app.get('/api/events', (_req, res) => {
    res.json({ events: getEvents() });
});
// イベント作成API
app.post('/api/events', (req, res) => {
    try {
        const { name, startDate, endDate, location } = req.body;
        // バリデーション
        if (!name || typeof name !== 'string' || name.trim().length === 0) {
            res.status(400).json({ message: 'イベント名は必須です' });
            return;
        }
        if (!startDate || typeof startDate !== 'string') {
            res.status(400).json({ message: '開始日は必須です' });
            return;
        }
        if (!endDate || typeof endDate !== 'string') {
            res.status(400).json({ message: '終了日は必須です' });
            return;
        }
        // 日付の妥当性チェック
        const start = new Date(startDate);
        const end = new Date(endDate);
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            res.status(400).json({ message: '有効な日付を入力してください' });
            return;
        }
        if (start > end) {
            res.status(400).json({ message: '開始日は終了日より前である必要があります' });
            return;
        }
        // イベント名の重複チェック
        const existingEvents = getEvents();
        if (existingEvents.some(event => event.name.toLowerCase() === name.toLowerCase())) {
            res.status(400).json({ message: '同じ名前のイベントが既に存在します' });
            return;
        }
        // 新しいイベントを作成
        const newEvent = {
            id: `event-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            name: name.trim(),
            startDate,
            endDate,
            location: location?.trim() || undefined,
        };
        // ストアに追加
        eventStore.set(newEvent.id, newEvent);
        res.status(201).json({ event: newEvent });
    }
    catch (error) {
        console.error('イベント作成エラー:', error);
        res.status(500).json({ message: 'イベントの作成に失敗しました' });
    }
});
app.get('/api/cards', (_req, res) => {
    res.json({ cards: getSummaries() });
});
app.get('/api/cards/:id', (req, res) => {
    const record = cardStore.get(req.params.id);
    if (!record) {
        res.status(404).json({ message: 'カードが見つかりません' });
        return;
    }
    res.json({ detail: record.detail });
});
// カード編集API
app.put('/api/cards/:id', (req, res) => {
    try {
        const { id } = req.params;
        const record = cardStore.get(id);
        if (!record) {
            res.status(404).json({ message: 'カードが見つかりません' });
            return;
        }
        const { title, companyName, eventId, authorId = record.detail.header.author.id, ocrText = record.detail.body.ocr.text, tags = [], memo, } = req.body ?? {};
        // バリデーション
        if (!title || typeof title !== 'string') {
            res.status(400).json({ message: 'タイトルは必須です' });
            return;
        }
        if (tags.length > LIMITS.tagsMaxPerCard) {
            res.status(400).json({ message: `タグは${LIMITS.tagsMaxPerCard}件以内です` });
            return;
        }
        // カードレコードを更新
        const nowIso = new Date().toISOString();
        const event = eventId ? getEvents().find((e) => e.id === eventId) : record.detail.header.event;
        const author = users.find((u) => u.id === authorId) ?? record.detail.header.author;
        // 詳細情報を更新
        record.detail.header.title = title;
        record.detail.header.companyName = companyName;
        record.detail.header.event = event;
        record.detail.header.author = author;
        record.detail.header.updatedAt = nowIso;
        record.detail.body.ocr.text = ocrText;
        record.detail.body.ocr.updatedAt = nowIso;
        record.detail.body.tags = tags;
        record.detail.body.highlights = memo ? [memo] : [];
        // サマリー情報を更新
        record.summary.title = title;
        record.summary.tags = tags;
        record.summary.eventId = event?.id;
        // 履歴に編集記録を追加
        record.detail.sidebar.history.push({
            action: 'updated',
            actor: author,
            at: nowIso,
            details: 'カードを編集',
        });
        // ストアに保存
        cardStore.set(id, record);
        saveCardsToFile(); // カード更新後に自動保存
        res.json({ card: record.detail, summary: record.summary });
    }
    catch (error) {
        console.error('カード編集エラー:', error);
        res.status(500).json({ message: 'カードの編集に失敗しました' });
    }
});
// カード削除API
app.delete('/api/cards/:id', (req, res) => {
    try {
        const { id } = req.params;
        const record = cardStore.get(id);
        if (!record) {
            res.status(404).json({ message: 'カードが見つかりません' });
            return;
        }
        // カードを削除
        cardStore.delete(id);
        saveCardsToFile(); // カード削除後に自動保存
        res.status(204).send(); // 204 No Content
    }
    catch (error) {
        console.error('カード削除エラー:', error);
        res.status(500).json({ message: 'カードの削除に失敗しました' });
    }
});
// ファイルアップロード用エンドポイント
app.post('/api/upload', upload.single('file'), async (req, res) => {
    try {
        if (!req.file) {
            res.status(400).json({ message: 'ファイルがアップロードされていません' });
            return;
        }
        const { title, companyName, eventId, authorId = 'user-ryo', tags = [], memo } = req.body ?? {};
        if (!title || typeof title !== 'string') {
            res.status(400).json({ message: 'タイトルは必須です' });
            return;
        }
        if (!eventId) {
            res.status(400).json({ message: 'イベントは必須です' });
            return;
        }
        // ファイルからテキストを抽出
        const extractedText = await extractTextFromFile(req.file.path, req.file.mimetype, { enableOCR: false });
        // タグをパース
        const parsedTags = typeof tags === 'string' ? JSON.parse(tags) : tags;
        const newRecord = createCardRecord({
            title,
            companyName,
            eventId,
            authorId,
            ocrText: extractedText,
            tags: parsedTags,
            memo,
        });
        // ファイル情報を更新
        newRecord.detail.body.sourceFiles = [{
                id: `${newRecord.id}-file`,
                filename: req.file.filename,
                mimeType: req.file.mimetype,
                bytes: req.file.size,
            }];
        newRecord.timeseries = [
            {
                date: new Date().toISOString().slice(0, 10),
                views: newRecord.reactions.views,
                comments: newRecord.reactions.comments,
                likes: newRecord.reactions.likes,
            },
        ];
        newRecord.audience = [
            { department: '営業', ratio: 0.4 },
            { department: '開発', ratio: 0.3 },
            { department: '企画', ratio: 0.3 },
        ];
        cardStore.set(newRecord.id, newRecord);
        saveCardsToFile(); // カード作成後に自動保存
        res.status(201).json({
            card: newRecord.detail,
            summary: newRecord.summary,
            extractedText,
            fileUrl: `/uploads/${req.file.filename}`
        });
    }
    catch (error) {
        console.error('アップロードエラー:', error);
        res.status(500).json({ message: error.message });
    }
});
// Webクリップ用のメタデータ生成関数
function generateWebClipMetadata(url, title, description) {
    const baseUrl = process.env.BASE_URL || 'http://localhost:5175';
    return {
        // Apple Webクリップ仕様に準拠したメタデータ
        appleWebApp: {
            capable: true,
            statusBarStyle: 'default',
            title: title || 'Webクリップ',
            startupImage: `${baseUrl}/assets/webclip-startup.png`,
            icons: [
                {
                    sizes: '57x57',
                    src: `${baseUrl}/assets/webclip-icon-57.png`,
                    type: 'image/png'
                },
                {
                    sizes: '72x72',
                    src: `${baseUrl}/assets/webclip-icon-72.png`,
                    type: 'image/png'
                },
                {
                    sizes: '114x114',
                    src: `${baseUrl}/assets/webclip-icon-114.png`,
                    type: 'image/png'
                },
                {
                    sizes: '144x144',
                    src: `${baseUrl}/assets/webclip-icon-144.png`,
                    type: 'image/png'
                },
                {
                    sizes: '180x180',
                    src: `${baseUrl}/assets/webclip-icon-180.png`,
                    type: 'image/png'
                }
            ]
        },
        // 標準的なWebアプリマニフェスト
        manifest: {
            name: title || 'Webクリップ',
            short_name: title?.substring(0, 12) || 'Webクリップ',
            description: description || 'Webクリップアプリケーション',
            start_url: url,
            display: 'standalone',
            orientation: 'portrait',
            theme_color: '#007AFF',
            background_color: '#FFFFFF',
            icons: [
                {
                    src: `${baseUrl}/assets/webclip-icon-192.png`,
                    sizes: '192x192',
                    type: 'image/png'
                },
                {
                    src: `${baseUrl}/assets/webclip-icon-512.png`,
                    sizes: '512x512',
                    type: 'image/png'
                }
            ]
        },
        // 構成プロファイル用の設定
        configProfile: {
            PayloadType: 'com.apple.webClip.managed',
            PayloadVersion: 1,
            PayloadIdentifier: `com.insightbox.webclip.${Date.now()}`,
            PayloadDisplayName: title || 'Webクリップ',
            PayloadDescription: description || 'Insight-Box Webクリップ',
            PayloadOrganization: 'Insight-Box',
            PayloadScope: 'User',
            URL: url,
            Label: title || 'Webクリップ',
            Icon: {
                Data: '', // Base64エンコードされたアイコンデータ
                ContentType: 'image/png'
            },
            IsRemovable: true,
            Precomposed: false,
            FullScreen: true,
            IgnoreManifestScope: false
        },
        configurationProfile: undefined
    };
}
// Webクリップ用エンドポイント
app.post('/api/webclip', (req, res) => {
    try {
        const { title, url, content, eventId, authorId = 'user-ryo', tags = [], memo, description } = req.body ?? {};
        if (!title || typeof title !== 'string') {
            res.status(400).json({ message: 'タイトルは必須です' });
            return;
        }
        if (!eventId) {
            res.status(400).json({ message: 'イベントは必須です' });
            return;
        }
        if (!url || typeof url !== 'string') {
            res.status(400).json({ message: 'URLは必須です' });
            return;
        }
        // URLの妥当性チェック
        try {
            new URL(url);
        }
        catch (error) {
            res.status(400).json({ message: '有効なURLを入力してください' });
            return;
        }
        const newRecord = createCardRecord({
            title,
            companyName: new URL(url).hostname,
            eventId,
            authorId,
            ocrText: content || `Webページ: ${url}`,
            tags,
            memo,
        });
        // Webクリップ用メタデータを生成
        const webClipMetadata = generateWebClipMetadata(url, title, description);
        // Webクリップ情報を追加
        newRecord.detail.body.sourceFiles = [{
                id: `${newRecord.id}-webclip`,
                filename: `${new URL(url).hostname}.html`,
                mimeType: 'text/html',
                bytes: content ? content.length : 0,
            }];
        newRecord.detail.body.highlights = [
            `Webクリップ: ${url}`,
            ...(memo ? [memo] : [])
        ];
        // Webクリップ用の追加メタデータを保存
        newRecord.detail.body.webClipMetadata = webClipMetadata;
        cardStore.set(newRecord.id, newRecord);
        saveCardsToFile(); // カード作成後に自動保存
        res.status(201).json({
            card: newRecord.detail,
            summary: newRecord.summary,
            webclipUrl: url,
            webClipMetadata: webClipMetadata
        });
    }
    catch (error) {
        console.error('Webクリップエラー:', error);
        res.status(500).json({ message: error.message });
    }
});
// Webクリップ用のHTML生成エンドポイント
app.get('/api/webclip/:cardId/html', (req, res) => {
    try {
        const { cardId } = req.params;
        const card = cardStore.get(cardId);
        if (!card) {
            res.status(404).json({ message: 'カードが見つかりません' });
            return;
        }
        const webClipMetadata = card.detail.body.webClipMetadata;
        if (!webClipMetadata) {
            res.status(404).json({ message: 'Webクリップメタデータが見つかりません' });
            return;
        }
        const html = `
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${webClipMetadata.manifest.name}</title>
    
    <!-- Apple Web App Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="${webClipMetadata.appleWebApp.statusBarStyle}">
    <meta name="apple-mobile-web-app-title" content="${webClipMetadata.appleWebApp.title}">
    
    <!-- Apple Touch Icons -->
    ${webClipMetadata.appleWebApp.icons.map((icon) => `<link rel="apple-touch-icon" sizes="${icon.sizes}" href="${icon.src}">`).join('\n    ')}
    
    <!-- Startup Image -->
    <link rel="apple-touch-startup-image" href="${webClipMetadata.appleWebApp.startupImage}">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="/api/webclip/${cardId}/manifest.json">
    
    <!-- Theme Colors -->
    <meta name="theme-color" content="${webClipMetadata.manifest.theme_color}">
    <meta name="msapplication-navbutton-color" content="${webClipMetadata.manifest.theme_color}">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: ${webClipMetadata.manifest.background_color};
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .description {
            color: #666;
            font-size: 16px;
        }
        .content {
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .url {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 8px;
            word-break: break-all;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: ${webClipMetadata.manifest.theme_color};
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="header">
            <div class="title">${webClipMetadata.manifest.name}</div>
            <div class="description">${webClipMetadata.manifest.description}</div>
        </div>
        
        <div class="url">
            <strong>URL:</strong> ${webClipMetadata.manifest.start_url}
        </div>
        
        <p>このWebクリップをホーム画面に追加するには、Safariで「共有」ボタンをタップし、「ホーム画面に追加」を選択してください。</p>
        
        <a href="${webClipMetadata.manifest.start_url}" class="button" target="_blank">
            元のサイトを開く
        </a>
        
        <a href="/api/webclip/${cardId}/manifest.json" class="button" download>
            マニフェストをダウンロード
        </a>
    </div>
</body>
</html>`;
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.send(html);
    }
    catch (error) {
        console.error('WebクリップHTML生成エラー:', error);
        res.status(500).json({ message: 'HTML生成に失敗しました' });
    }
});
// Webクリップ用のマニフェスト生成エンドポイント
app.get('/api/webclip/:cardId/manifest.json', (req, res) => {
    try {
        const { cardId } = req.params;
        const card = cardStore.get(cardId);
        if (!card) {
            res.status(404).json({ message: 'カードが見つかりません' });
            return;
        }
        const webClipMetadata = card.detail.body.webClipMetadata;
        if (!webClipMetadata) {
            res.status(404).json({ message: 'Webクリップメタデータが見つかりません' });
            return;
        }
        res.setHeader('Content-Type', 'application/json');
        res.json(webClipMetadata.manifest);
    }
    catch (error) {
        console.error('Webクリップマニフェスト生成エラー:', error);
        res.status(500).json({ message: 'マニフェスト生成に失敗しました' });
    }
});
// Webクリップ用の構成プロファイル生成エンドポイント
app.get('/api/webclip/:cardId/profile.mobileconfig', (req, res) => {
    try {
        const { cardId } = req.params;
        const card = cardStore.get(cardId);
        if (!card) {
            res.status(404).json({ message: 'カードが見つかりません' });
            return;
        }
        const webClipMetadata = card.detail.body.webClipMetadata;
        if (!webClipMetadata) {
            res.status(404).json({ message: 'Webクリップメタデータが見つかりません' });
            return;
        }
        const profile = webClipMetadata.configProfile ?? webClipMetadata.configurationProfile ?? {};
        res.setHeader('Content-Type', 'application/x-apple-aspen-config');
        res.setHeader('Content-Disposition', `attachment; filename="webclip-${cardId}.mobileconfig"`);
        res.send(JSON.stringify(profile, null, 2));
    }
    catch (error) {
        console.error('Webクリップ構成プロファイル生成エラー:', error);
        res.status(500).json({ message: '構成プロファイル生成に失敗しました' });
    }
});
app.post('/api/cards', (req, res) => {
    const { title, companyName, eventId, authorId = 'user-ryo', ocrText = '', tags = [], memo, } = req.body ?? {};
    if (!title || typeof title !== 'string') {
        res.status(400).json({ message: 'タイトルは必須です' });
        return;
    }
    if (!eventId) {
        res.status(400).json({ message: 'イベントは必須です' });
        return;
    }
    if (tags.length > LIMITS.tagsMaxPerCard) {
        res.status(400).json({ message: `タグは${LIMITS.tagsMaxPerCard}件以内です` });
        return;
    }
    const newRecord = createCardRecord({
        title,
        companyName,
        eventId,
        authorId,
        ocrText,
        tags,
        memo,
    });
    newRecord.timeseries = [
        {
            date: new Date().toISOString().slice(0, 10),
            views: newRecord.reactions.views,
            comments: newRecord.reactions.comments,
            likes: newRecord.reactions.likes,
        },
    ];
    newRecord.audience = [
        { department: '営業', ratio: 0.4 },
        { department: '開発', ratio: 0.3 },
        { department: '企画', ratio: 0.3 },
    ];
    cardStore.set(newRecord.id, newRecord);
    saveCardsToFile(); // カード作成後に自動保存
    res.status(201).json({ card: newRecord.detail, summary: newRecord.summary });
});
app.get('/api/board', async (req, res) => {
    try {
        const outputs = await boardService.loadBoard({
            tagIds: req.query.tagIds ? String(req.query.tagIds).split(',') : undefined,
            eventIds: req.query.eventIds ? String(req.query.eventIds).split(',') : undefined,
        });
        res.json(outputs);
    }
    catch (error) {
        res.status(500).json({ message: error.message });
    }
});
app.post('/api/templates/apply', async (req, res) => {
    const inputs = req.body;
    const summarize = async (slot) => `${slot.label}:
${slot.cardIds.length}件のカードを配置`;
    try {
        const result = await boardService.applyTemplate(inputs, summarize);
        res.json(result);
    }
    catch (error) {
        res.status(500).json({ message: error.message });
    }
});
app.get('/api/marketplace', (_req, res) => {
    const cards = getMarketplaceCards();
    const response = {
        cards,
        state: 'success',
    };
    res.json(response);
});
app.post('/api/share', (req, res) => {
    const inputs = req.body;
    if (!inputs?.cardId) {
        res.status(400).json({ message: 'cardIdが必要です' });
        return;
    }
    const record = cardStore.get(inputs.cardId);
    if (!record) {
        res.status(404).json({ message: 'カードが見つかりません' });
        return;
    }
    const shareUrl = `https://insight-box.local/share/${inputs.cardId}?token=${uuid().slice(0, 8)}`;
    const result = {
        shareUrl,
        postedToSlack: Boolean(inputs.slackTargets?.length),
        state: 'success',
    };
    res.json(result);
});
let settings = {
    defaultScope: 'team',
    eventUploadMax: LIMITS.eventUploadMax,
};
app.get('/api/settings/roles', (_req, res) => {
    res.json({ settings });
});
app.post('/api/settings/roles', (req, res) => {
    const next = req.body;
    if (!['team', 'org'].includes(next.defaultScope)) {
        res.status(400).json({ message: 'defaultScopeが不正です' });
        return;
    }
    if (next.eventUploadMax < 1 || next.eventUploadMax > 200) {
        res.status(400).json({ message: 'eventUploadMaxが範囲外です' });
        return;
    }
    settings = next;
    res.json({ applied: true, state: 'success' });
});
app.get('/api/analytics/personal', (_req, res) => {
    const reactions = Array.from(cardStore.values()).reduce((acc, record) => ({
        views: acc.views + record.reactions.views,
        comments: acc.comments + record.reactions.comments,
        likes: acc.likes + record.reactions.likes,
    }), { views: 0, comments: 0, likes: 0 });
    const timeseries = Array.from(cardStore.values())[0]?.timeseries ?? [];
    res.json({
        kpis: reactions,
        timeseries,
        state: 'success',
    });
});
app.get('/api/analytics/cards/:id', (req, res) => {
    const record = cardStore.get(req.params.id);
    if (!record) {
        res.status(404).json({ message: 'カードが見つかりません' });
        return;
    }
    res.json({
        kpis: record.reactions,
        timeseries: record.timeseries,
        audienceDistribution: record.audience,
        state: 'success',
    });
});
// OCR品質チェック関数
function checkOCRQuality(text) {
    const issues = [];
    let quality = 'high';
    // 意味不明な文字列の検出
    const gibberishPattern = /[A-Z]{4,}[^a-z\s]{2,}|[A-Z]{2,}[^a-z\s]{4,}/g;
    const gibberishMatches = text.match(gibberishPattern);
    if (gibberishMatches && gibberishMatches.length > 0) {
        issues.push(`意味不明な文字列を検出: ${gibberishMatches.slice(0, 3).join(', ')}`);
        quality = 'low';
    }
    // 連続する大文字の検出
    const consecutiveCaps = /[A-Z]{5,}/g;
    const capsMatches = text.match(consecutiveCaps);
    if (capsMatches && capsMatches.length > 0) {
        issues.push(`連続する大文字を検出: ${capsMatches.slice(0, 3).join(', ')}`);
        if (quality === 'high')
            quality = 'medium';
    }
    // 文字密度のチェック
    const totalChars = text.length;
    const japaneseChars = (text.match(/[あ-んア-ン一-龯]/g) || []).length;
    const englishChars = (text.match(/[a-zA-Z]/g) || []).length;
    const numberChars = (text.match(/[0-9]/g) || []).length;
    const otherChars = totalChars - japaneseChars - englishChars - numberChars;
    if (otherChars / totalChars > 0.3) {
        issues.push('特殊文字の割合が高い');
        if (quality === 'high')
            quality = 'medium';
    }
    // 文字認識の信頼度推定
    const commonWords = ['株式会社', '有限会社', '合同会社', '株式会社', 'the', 'and', 'or', 'of', 'in', 'to', 'for'];
    const foundCommonWords = commonWords.filter(word => text.toLowerCase().includes(word.toLowerCase())).length;
    if (foundCommonWords === 0 && totalChars > 50) {
        issues.push('一般的な単語が検出されない');
        if (quality === 'high')
            quality = 'medium';
    }
    return { quality, issues };
}
// OCR結果の後処理と品質改善
function improveOCRResult(text) {
    const qualityCheck = checkOCRQuality(text);
    let improvedText = text;
    // 意味不明な文字列の修正試行
    if (qualityCheck.quality === 'low') {
        // 一般的なOCR誤りパターンの修正
        const corrections = [
            // よくある文字の誤認識パターン
            { pattern: /BOLAHHERII/g, replacement: '株式会社' },
            { pattern: /FEDRIOEETR/g, replacement: '有限会社' },
            { pattern: /RORKEMHFLI/g, replacement: '合同会社' },
            { pattern: /ZELRENR/g, replacement: '株式会社' },
            { pattern: /BRRSh/g, replacement: '株式会社' },
            { pattern: /Y—Z/g, replacement: '株式会社' },
            // その他の一般的なパターン
            { pattern: /[A-Z]{4,}[^a-z\s]{2,}/g, replacement: '[認識エラー]' },
            { pattern: /[A-Z]{2,}[^a-z\s]{4,}/g, replacement: '[認識エラー]' },
        ];
        corrections.forEach(correction => {
            improvedText = improvedText.replace(correction.pattern, correction.replacement);
        });
    }
    // 改善後の品質再チェック
    const improvedQualityCheck = checkOCRQuality(improvedText);
    return {
        improvedText,
        quality: improvedQualityCheck.quality,
        issues: improvedQualityCheck.issues
    };
}
// エンティティ抽出関数
function extractEntities(text) {
    const entities = {
        company: [],
        products: [],
        contacts: {
            email: [],
            tel: [],
            url: []
        }
    };
    // メールアドレス抽出
    const emailRegex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
    entities.contacts.email = [...new Set(text.match(emailRegex) || [])];
    // 電話番号抽出（日本の形式）
    const telRegex = /(\d{2,4}-\d{2,4}-\d{4}|\d{10,11}|\d{3}-\d{4}-\d{4})/g;
    entities.contacts.tel = [...new Set(text.match(telRegex) || [])];
    // URL抽出
    const urlRegex = /https?:\/\/[^\s]+/g;
    entities.contacts.url = [...new Set(text.match(urlRegex) || [])];
    // 会社名抽出（株式会社、有限会社、合同会社等）
    const companyRegex = /([^\s]+(?:株式会社|有限会社|合同会社|合資会社|合名会社|一般社団法人|一般財団法人|公益社団法人|公益財団法人|学校法人|医療法人|社会福祉法人|NPO法人|協同組合|事業協同組合|企業組合|協業組合|信用組合|信用金庫|労働金庫|農業協同組合|漁業協同組合|森林組合|生活協同組合|消費生活協同組合)[^\s]*)/g;
    entities.company = [...new Set(text.match(companyRegex) || [])];
    // 製品名抽出（一般的な製品名パターン）
    const productRegex = /([A-Z][a-zA-Z0-9\s]+(?:Suite|System|Platform|Service|Solution|Cloud|AI|IoT|API|SDK|Framework|Engine|Studio|Pro|Enterprise|Business|Standard|Basic|Premium|Advanced|Professional)[^\s]*)/g;
    entities.products = [...new Set(text.match(productRegex) || [])];
    return entities;
}
// レイアウト解析関数
function analyzeLayout(text) {
    const layout = [];
    const lines = text.split('\n');
    lines.forEach((line, index) => {
        const trimmedLine = line.trim();
        if (!trimmedLine)
            return;
        let type = 'paragraph';
        // 見出し判定
        if (/^\d+\.\d*/.test(trimmedLine) || /^[#*]{1,3}\s/.test(trimmedLine)) {
            type = 'heading';
        }
        // 箇条書き判定
        else if (/^[・\*\-\u2022]\s/.test(trimmedLine)) {
            type = 'list';
        }
        // 表判定（簡易）
        else if (/\t/.test(trimmedLine) || /\s{3,}/.test(trimmedLine)) {
            type = 'table';
        }
        layout.push({
            type,
            bbox: [0, index * 20, 100, (index + 1) * 20], // 簡易的な座標
            text: trimmedLine
        });
    });
    return layout;
}
// タグ生成関数
function generateTags(entities, text) {
    const tags = [];
    // エンティティベースのタグ
    if (entities.contacts.email.length > 0)
        tags.push('連絡先');
    if (entities.contacts.tel.length > 0)
        tags.push('電話番号');
    if (entities.contacts.url.length > 0)
        tags.push('Webサイト');
    if (entities.company.length > 0)
        tags.push('企業情報');
    if (entities.products.length > 0)
        tags.push('製品情報');
    // キーワードベースのタグ
    const keywords = {
        'AI': /AI|人工知能|機械学習|深層学習|ディープラーニング/i,
        'SaaS': /SaaS|クラウド|クラウドサービス|ソフトウェア/i,
        '製造業': /製造|工場|生産|品質管理|IoT|産業/i,
        'IT': /IT|情報技術|システム|ソフトウェア|アプリ/i,
        'マーケティング': /マーケティング|営業|販売|プロモーション/i,
        '人事': /人事|採用|人材|HR|組織/i,
        '財務': /財務|会計|経理|予算|資金/i
    };
    Object.entries(keywords).forEach(([tag, regex]) => {
        if (regex.test(text)) {
            tags.push(tag);
        }
    });
    return [...new Set(tags)];
}
// モバイル一次OCR用エンドポイント
app.post('/api/ingest', upload.single('file'), async (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({ error: 'ファイルがアップロードされていません' });
        }
        const docId = `doc_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const startTime = Date.now();
        // 一次OCR処理（軽量・高速）
        const rawText = await extractTextFromImage(req.file.path);
        const primaryTime = Date.now() - startTime;
        // OCR品質チェックと改善
        const { improvedText: primaryText, quality, issues } = improveOCRResult(rawText);
        const primaryConfidence = quality === 'high' ? 0.85 : quality === 'medium' ? 0.65 : 0.35;
        // エンティティ抽出
        const entities = extractEntities(primaryText);
        const layout = analyzeLayout(primaryText);
        const tags = generateTags(entities, primaryText);
        // ハイブリッドOCR結果の構築
        const result = {
            doc_id: docId,
            source: {
                expo: req.body.expo || 'Unknown Expo',
                booth: req.body.booth || 'Unknown Booth',
                captured_at: new Date().toISOString(),
                device: req.body.device || 'Unknown Device',
                event_id: req.body.event_id
            },
            ocr: {
                text: primaryText,
                engine: 'mobile_primary',
                confidence_avg: primaryConfidence,
                processing_time_ms: primaryTime,
                quality,
                quality_issues: issues
            },
            entities,
            layout,
            tags,
            file: {
                original_url: `/uploads/${req.file.filename}`,
                thumb_url: `/uploads/${req.file.filename}`
            },
            metadata: {
                page_count: 1,
                file_size: req.file.size,
                mime_type: req.file.mimetype
            }
        };
        // 結果を保存（簡易的なメモリ保存）
        if (!global.hybridResults) {
            global.hybridResults = new Map();
        }
        if (!global.documents) {
            global.documents = new Map();
        }
        if (!global.jobs) {
            global.jobs = new Map();
        }
        global.hybridResults.set(docId, result);
        res.json({
            success: true,
            doc_id: docId,
            result,
            message: '一次OCR処理が完了しました。帰社後に高精度仕上げを実行できます。'
        });
    }
    catch (error) {
        console.error('一次OCR処理エラー:', error);
        res.status(500).json({ error: '一次OCR処理中にエラーが発生しました' });
    }
});
// 高精度再処理エンドポイント
app.post('/api/re-ocr/:docId', async (req, res) => {
    try {
        const { docId } = req.params;
        const { engine = 'ppocrv4' } = req.body;
        const hybridResults = getGlobalHybridResults();
        if (!hybridResults.has(docId)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const originalResult = hybridResults.get(docId);
        const startTime = Date.now();
        // 高精度OCR処理
        let rawHighPrecisionText;
        let confidence;
        let engineUsed;
        switch (engine) {
            case 'ppocrv4':
                // PaddleOCR v4のシミュレーション（実際の実装では外部API呼び出し）
                rawHighPrecisionText = await extractTextFromImage(originalResult.file.original_url.replace('/uploads/', './uploads/'));
                confidence = 0.95;
                engineUsed = 'ppocrv4';
                break;
            case 'trocr':
                // TrOCRのシミュレーション
                rawHighPrecisionText = await extractTextFromImage(originalResult.file.original_url.replace('/uploads/', './uploads/'));
                confidence = 0.92;
                engineUsed = 'trocr';
                break;
            case 'tesseract_jpn_vert':
                // Tesseract縦書きのシミュレーション
                rawHighPrecisionText = await extractTextFromImage(originalResult.file.original_url.replace('/uploads/', './uploads/'));
                confidence = 0.88;
                engineUsed = 'tesseract_jpn_vert';
                break;
            default:
                return res.status(400).json({ error: 'サポートされていないOCRエンジンです' });
        }
        // 高精度OCR結果の品質チェックと改善
        const { improvedText: highPrecisionText, quality, issues } = improveOCRResult(rawHighPrecisionText);
        const adjustedConfidence = quality === 'high' ? confidence : quality === 'medium' ? confidence * 0.8 : confidence * 0.6;
        const processingTime = Date.now() - startTime;
        // 高精度結果でエンティティ再抽出
        const highPrecisionEntities = extractEntities(highPrecisionText);
        const highPrecisionLayout = analyzeLayout(highPrecisionText);
        const highPrecisionTags = generateTags(highPrecisionEntities, highPrecisionText);
        // 結果を更新
        const updatedResult = {
            ...originalResult,
            doc_id: originalResult.doc_id,
            ocr: {
                text: highPrecisionText,
                engine: engineUsed,
                confidence_avg: adjustedConfidence,
                processing_time_ms: processingTime,
                quality,
                quality_issues: issues
            },
            entities: highPrecisionEntities,
            layout: highPrecisionLayout,
            tags: highPrecisionTags
        };
        hybridResults.set(docId, updatedResult);
        res.json({
            success: true,
            doc_id: docId,
            result: updatedResult,
            message: '高精度OCR処理が完了しました。'
        });
    }
    catch (error) {
        console.error('高精度OCR処理エラー:', error);
        res.status(500).json({ error: '高精度OCR処理中にエラーが発生しました' });
    }
});
// 検索エンドポイント
app.get('/api/search', (req, res) => {
    try {
        const { q: query, filters } = req.query;
        if (!global.hybridResults) {
            return res.json({ results: [], total: 0 });
        }
        let results = Array.from(global.hybridResults.values());
        // テキスト検索
        if (query) {
            const searchQuery = query.toString().toLowerCase();
            results = results.filter(result => result.ocr.text.toLowerCase().includes(searchQuery) ||
                result.entities.company.some(company => company.toLowerCase().includes(searchQuery)) ||
                result.entities.products.some(product => product.toLowerCase().includes(searchQuery)) ||
                result.tags.some(tag => tag.toLowerCase().includes(searchQuery)));
        }
        // フィルタリング
        if (filters) {
            const filterArray = filters.toString().split(',');
            results = results.filter(result => filterArray.some(filter => result.tags.includes(filter)));
        }
        res.json({
            results: results.slice(0, 50), // 最大50件
            total: results.length,
            query: query || '',
            filters: filters || ''
        });
    }
    catch (error) {
        console.error('検索エラー:', error);
        res.status(500).json({ error: '検索中にエラーが発生しました' });
    }
});
// ドキュメント詳細取得エンドポイント
app.get('/api/docs/:docId', (req, res) => {
    try {
        const { docId } = req.params;
        if (!global.hybridResults || !global.hybridResults.has(docId)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const result = global.hybridResults.get(docId);
        res.json({ success: true, result });
    }
    catch (error) {
        console.error('ドキュメント取得エラー:', error);
        res.status(500).json({ error: 'ドキュメント取得中にエラーが発生しました' });
    }
});
// ハイブリッドOCR結果一覧取得
app.get('/api/hybrid-results', (req, res) => {
    try {
        if (!global.hybridResults) {
            return res.json({ results: [], total: 0 });
        }
        const results = Array.from(global.hybridResults.values());
        res.json({
            results: results.slice(0, 100), // 最大100件
            total: results.length
        });
    }
    catch (error) {
        console.error('ハイブリッド結果取得エラー:', error);
        res.status(500).json({ error: '結果取得中にエラーが発生しました' });
    }
});
// PDF分析専用エンドポイント
app.post('/api/analyze-pdf', upload.single('file'), async (req, res) => {
    try {
        if (!req.file) {
            res.status(400).json({ message: 'ファイルがアップロードされていません' });
            return;
        }
        if (req.file.mimetype !== 'application/pdf') {
            res.status(400).json({ message: 'PDFファイルのみサポートしています' });
            return;
        }
        const { enableOCR = false, detailedAnalysis = true } = req.body ?? {};
        // 詳細なPDF分析を実行
        const analysisResult = await extractTextFromPDF(req.file.path, enableOCR === 'true' || enableOCR === true, undefined);
        // 追加の分析情報
        const stats = fs.statSync(req.file.path);
        const fileSizeKB = Math.round(stats.size / 1024);
        const response = {
            success: true,
            fileName: req.file.originalname,
            fileSize: fileSizeKB,
            mimeType: req.file.mimetype,
            analysisResult,
            analysisOptions: {
                enableOCR: enableOCR === 'true' || enableOCR === true,
                detailedAnalysis: detailedAnalysis === 'true' || detailedAnalysis === true
            },
            timestamp: new Date().toISOString()
        };
        res.json(response);
    }
    catch (error) {
        console.error('PDF分析エラー:', error);
        res.status(500).json({
            success: false,
            message: 'PDF分析中にエラーが発生しました',
            error: error.message
        });
    }
});
// ===== 統一REST API v1 =====
// 1) ドキュメント作成（アップロード/URL取り込み）
app.post('/v1/documents', upload.single('file'), async (req, res) => {
    try {
        console.log('POST /v1/documents リクエスト受信');
        console.log('req.body:', JSON.stringify(req.body, null, 2));
        console.log('req.file:', req.file);
        // ファイルアップロードの場合の処理
        let source, tasks, meta, options;
        if (req.file) {
            // ファイルアップロードの場合
            const payload = JSON.parse(req.body.payload || '{}');
            source = {
                type: 'file',
                filename: req.file.filename,
                size: req.file.size,
                mime_type: req.file.mimetype
            };
            tasks = payload.tasks || ['ocr'];
            meta = payload.meta || {};
            options = payload.options || {};
        }
        else {
            // URLの場合
            const body = req.body;
            source = body.source;
            tasks = body.tasks;
            meta = body.meta;
            options = body.options;
        }
        const idempotencyKey = req.headers['idempotency-key'];
        // ドキュメントIDを生成
        const documentId = `doc_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const jobId = `job_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        console.log('生成されたID:', { documentId, jobId });
        console.log('処理するデータ:', { source, tasks, meta, options });
        // ドキュメント作成
        const document = {
            id: documentId,
            source,
            meta: {
                ...meta,
                captured_at: new Date().toISOString()
            },
            tasks,
            status: 'queued',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            artifacts: {}
        };
        // ジョブ作成
        const job = {
            id: jobId,
            document_id: documentId,
            status: 'queued',
            progress: 0,
            tasks,
            created_at: new Date().toISOString(),
            artifacts: []
        };
        // 保存
        if (!global.documents)
            global.documents = new Map();
        if (!global.jobs)
            global.jobs = new Map();
        global.documents.set(documentId, document);
        global.jobs.set(jobId, job);
        // 非同期処理を開始
        console.log('processDocumentAsync呼び出し開始');
        processDocumentAsync(documentId, jobId, source, tasks, options);
        console.log('processDocumentAsync呼び出し完了');
        res.status(202).json({
            document_id: documentId,
            job_id: jobId,
            status: 'queued'
        });
    }
    catch (error) {
        console.error('ドキュメント作成エラー:', error);
        res.status(500).json({ error: 'ドキュメント作成中にエラーが発生しました' });
    }
});
// 2) ジョブ確認・完了結果
app.get('/v1/jobs/:jobId', (req, res) => {
    try {
        const { jobId } = req.params;
        if (!global.jobs || !global.jobs.has(jobId)) {
            return res.status(404).json({ error: 'ジョブが見つかりません' });
        }
        const job = global.jobs.get(jobId);
        res.json(job);
    }
    catch (error) {
        console.error('ジョブ取得エラー:', error);
        res.status(500).json({ error: 'ジョブ取得中にエラーが発生しました' });
    }
});
// 3) ドキュメント取得（統一インターフェース）
app.get('/v1/documents/:id', (req, res) => {
    try {
        const { id } = req.params;
        if (!global.documents || !global.documents.has(id)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const document = global.documents.get(id);
        res.json(document);
    }
    catch (error) {
        console.error('ドキュメント取得エラー:', error);
        res.status(500).json({ error: 'ドキュメント取得中にエラーが発生しました' });
    }
});
// ドキュメントのテキスト取得
app.get('/v1/documents/:id/text', (req, res) => {
    try {
        const { id } = req.params;
        if (!global.documents || !global.documents.has(id)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const document = global.documents.get(id);
        res.json({ text: document.artifacts.text || '' });
    }
    catch (error) {
        console.error('テキスト取得エラー:', error);
        res.status(500).json({ error: 'テキスト取得中にエラーが発生しました' });
    }
});
// ドキュメントのエンティティ取得
app.get('/v1/documents/:id/entities', (req, res) => {
    try {
        const { id } = req.params;
        if (!global.documents || !global.documents.has(id)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const document = global.documents.get(id);
        res.json({ entities: document.artifacts.entities || {} });
    }
    catch (error) {
        console.error('エンティティ取得エラー:', error);
        res.status(500).json({ error: 'エンティティ取得中にエラーが発生しました' });
    }
});
// ドキュメントのページ取得
app.get('/v1/documents/:id/pages/:pageNum', (req, res) => {
    try {
        const { id, pageNum } = req.params;
        const pageIndex = parseInt(pageNum) - 1;
        if (!global.documents || !global.documents.has(id)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const document = global.documents.get(id);
        const pages = document.artifacts.pages || [];
        if (pageIndex < 0 || pageIndex >= pages.length) {
            return res.status(404).json({ error: 'ページが見つかりません' });
        }
        res.json({ page: pages[pageIndex] });
    }
    catch (error) {
        console.error('ページ取得エラー:', error);
        res.status(500).json({ error: 'ページ取得中にエラーが発生しました' });
    }
});
// 検索
app.get('/v1/search', (req, res) => {
    try {
        const { q: query, filters } = req.query;
        if (!global.documents) {
            return res.json({ results: [], total: 0 });
        }
        let results = Array.from(global.documents.values());
        // テキスト検索
        if (query) {
            const searchQuery = query.toLowerCase();
            results = results.filter(doc => doc.artifacts.text?.toLowerCase().includes(searchQuery) ||
                doc.meta.expo?.toLowerCase().includes(searchQuery) ||
                doc.meta.booth?.toLowerCase().includes(searchQuery));
        }
        // フィルタリング
        if (filters) {
            const filterArray = Array.isArray(filters) ? filters : [filters];
            results = results.filter(doc => filterArray.some(filter => doc.tasks.includes(filter)));
        }
        res.json({
            results: results.slice(0, 50), // 最大50件
            total: results.length
        });
    }
    catch (error) {
        console.error('検索エラー:', error);
        res.status(500).json({ error: '検索中にエラーが発生しました' });
    }
});
// ドキュメント再処理
app.post('/v1/documents/:id/reprocess', async (req, res) => {
    try {
        const { id } = req.params;
        const { tasks, options } = req.body;
        if (!global.documents || !global.documents.has(id)) {
            return res.status(404).json({ error: 'ドキュメントが見つかりません' });
        }
        const document = global.documents.get(id);
        const jobId = `job_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        // 新しいジョブ作成
        const job = {
            id: jobId,
            document_id: id,
            status: 'queued',
            progress: 0,
            tasks: tasks || document.tasks,
            created_at: new Date().toISOString(),
            artifacts: []
        };
        if (!global.jobs)
            global.jobs = new Map();
        global.jobs.set(jobId, job);
        // 非同期処理を開始
        const tasksToProcess = tasks || document.tasks || [];
        processDocumentAsync(id, jobId, document.source, tasksToProcess, options);
        res.status(202).json({
            job_id: jobId,
            status: 'queued'
        });
    }
    catch (error) {
        console.error('再処理エラー:', error);
        res.status(500).json({ error: '再処理中にエラーが発生しました' });
    }
});
// 非同期ドキュメント処理関数
async function processDocumentAsync(documentId, jobId, source, tasks, options) {
    try {
        console.log('processDocumentAsync開始:', { documentId, jobId, source, tasks, options });
        if (!global.jobs)
            global.jobs = new Map();
        const job = global.jobs.get(jobId);
        if (!job) {
            console.error('ジョブが見つかりません:', jobId);
            return;
        }
        // ジョブステータスを更新
        job.status = 'processing';
        job.started_at = new Date().toISOString();
        job.progress = 10;
        global.jobs.set(jobId, job);
        console.log('ジョブステータス更新:', job.status);
        let artifacts = {};
        // タスクごとに処理
        const tasksArray = Array.isArray(tasks) ? tasks : [];
        console.log('処理するタスク:', tasksArray);
        for (const task of tasksArray) {
            console.log('タスク処理開始:', task);
            switch (task) {
                case 'ocr':
                    console.log('OCRタスク開始, source.type:', source.type);
                    if (source.type === 'file') {
                        console.log('OCR処理実行');
                        const ocrResult = await processOCRTask(source, options, jobId);
                        console.log('OCR処理完了:', ocrResult.text.length, '文字');
                        artifacts.text = ocrResult.text;
                        artifacts.entities = ocrResult.entities;
                    }
                    else {
                        console.log('OCR処理スキップ: source.typeがfileではありません');
                    }
                    break;
                case 'web_clip':
                    if (source.type === 'url') {
                        const webClipResult = await processWebClipTask(source.url, options);
                        artifacts.text = webClipResult.text;
                        artifacts.entities = webClipResult.entities;
                    }
                    break;
                case 'pdf_analyze':
                    if (source.type === 'file') {
                        const pdfResult = await processPDFAnalyzeTask(source, options);
                        artifacts.text = pdfResult.text;
                        artifacts.pages = pdfResult.pages;
                        artifacts.entities = pdfResult.entities;
                    }
                    break;
            }
            // 進捗率は各タスク内で更新されるため、ここでは更新しない
            // job.progress += 30;
            // global.jobs.set(jobId, job);
        }
        // ドキュメントを更新
        if (!global.documents)
            global.documents = new Map();
        const document = global.documents.get(documentId);
        if (document) {
            document.artifacts = artifacts;
            document.status = 'completed';
            document.updated_at = new Date().toISOString();
            global.documents.set(documentId, document);
        }
        // ジョブ完了
        job.status = 'succeeded';
        job.progress = 100;
        job.completed_at = new Date().toISOString();
        job.artifacts = [
            { type: 'text', url: `/v1/documents/${documentId}/text` },
            { type: 'entities', url: `/v1/documents/${documentId}/entities` }
        ];
        global.jobs.set(jobId, job);
    }
    catch (error) {
        console.error('ドキュメント処理エラー:', error);
        // エラー処理
        if (!global.jobs)
            global.jobs = new Map();
        const job = global.jobs.get(jobId);
        if (job) {
            job.status = 'failed';
            job.error = error instanceof Error ? error.message : 'Unknown error';
            job.completed_at = new Date().toISOString();
            global.jobs.set(jobId, job);
        }
        if (!global.documents)
            global.documents = new Map();
        const document = global.documents.get(documentId);
        if (document) {
            document.status = 'failed';
            document.updated_at = new Date().toISOString();
            global.documents.set(documentId, document);
        }
    }
}
// ===== ワーカーシステム =====
// OCRワーカー（画像・スキャン）
async function processOCRTask(source, options, jobId) {
    try {
        console.log('OCRワーカー開始:', source.filename);
        console.log('source:', JSON.stringify(source, null, 2));
        // ファイルパスを構築
        const filePath = path.join(process.cwd(), 'uploads', source.filename);
        console.log('ファイルパス:', filePath);
        // ファイルの存在確認
        if (!fs.existsSync(filePath)) {
            console.error('ファイルが存在しません:', filePath);
            return {
                text: 'ファイルが見つかりません',
                entities: { company: [], products: [], contacts: { email: [], tel: [], url: [] } },
                layout: [],
                tags: [],
            };
        }
        // 画像のテキスト抽出（進捗率付き）
        console.log('テキスト抽出開始');
        const extractedText = await extractTextFromImage(filePath, jobId);
        console.log('テキスト抽出完了:', extractedText.length, '文字');
        // エンティティ抽出
        const entities = extractEntities(extractedText);
        // レイアウト分析
        const layout = analyzeLayout(extractedText);
        // タグ生成
        const tags = generateTags(entities, extractedText);
        return {
            text: extractedText,
            entities,
            layout,
            tags,
            metadata: {
                file_size: source.size,
                mime_type: source.mime_type,
                processing_time: Date.now()
            }
        };
    }
    catch (error) {
        console.error('OCRワーカーエラー:', error);
        throw error;
    }
}
// Webクリップワーカー（URL→HTML取得→本文抽出）
async function processWebClipTask(url, options) {
    try {
        console.log('Webクリップワーカー開始:', url);
        // URLからHTMLを取得（簡易実装）
        const response = await fetch(url);
        const html = await response.text();
        // HTMLから本文を抽出（簡易実装）
        const text = extractTextFromHTML(html);
        // エンティティ抽出
        const entities = extractEntities(text);
        // メタデータ抽出
        const metadata = extractWebMetadata(html);
        return {
            text,
            entities,
            metadata: {
                url,
                title: metadata.title,
                description: metadata.description,
                processing_time: Date.now()
            }
        };
    }
    catch (error) {
        console.error('Webクリップワーカーエラー:', error);
        throw error;
    }
}
// PDFワーカー（PDF→ページ分解→OCR/解析）
async function processPDFAnalyzeTask(source, options) {
    try {
        console.log('PDFワーカー開始:', source.filename);
        // ファイルパスを構築
        const filePath = path.join(process.cwd(), 'uploads', source.filename);
        // PDF分析実行
        const analysisResult = await extractTextFromPDF(filePath, options?.enableOCR || false, undefined);
        // エンティティ抽出
        const entities = extractEntities(analysisResult);
        // ページ情報の解析
        const pages = parsePDFPages(analysisResult);
        return {
            text: analysisResult,
            entities,
            pages,
            metadata: {
                file_size: source.size,
                mime_type: source.mime_type,
                processing_time: Date.now()
            }
        };
    }
    catch (error) {
        console.error('PDFワーカーエラー:', error);
        throw error;
    }
}
// 共通処理関数
function extractTextFromHTML(html) {
    // 簡易的なHTMLテキスト抽出
    return html
        .replace(/<script[^>]*>.*?<\/script>/gis, '')
        .replace(/<style[^>]*>.*?<\/style>/gis, '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}
function extractWebMetadata(html) {
    const titleMatch = html.match(/<title[^>]*>(.*?)<\/title>/i);
    const descMatch = html.match(/<meta[^>]*name=["']description["'][^>]*content=["']([^"']*)["']/i);
    return {
        title: titleMatch ? titleMatch[1].trim() : '',
        description: descMatch ? descMatch[1].trim() : ''
    };
}
function parsePDFPages(text) {
    // PDFのページ情報を解析（簡易実装）
    const pages = text.split('=== ページ');
    return pages.slice(1).map((page, index) => ({
        page_number: index + 1,
        text: page.replace(/^ \d+ ===\n/, '').trim(),
        word_count: page.split(/\s+/).length
    }));
}
app.listen(PORT, () => {
    console.log(`Insight-Box server running on http://localhost:${PORT}`);
});
async function evaluateOrientationVariants(filePath, jobId) {
    const angles = [0, 90, 180, 270];
    const cleanupPaths = [];
    let bestPath = filePath;
    let bestAngles = [0];
    let bestConfidence = -1;
    for (const angle of angles) {
        let candidatePath = filePath;
        if (angle !== 0) {
            candidatePath = createTempFilePath(`rot${angle}`);
            await sharp(filePath)
                .rotate(angle, { background: { r: 255, g: 255, b: 255, alpha: 1 } })
                .png({ quality: 100 })
                .toFile(candidatePath);
            cleanupPaths.push(candidatePath);
        }
        try {
            const { data } = await Tesseract.recognize(candidatePath, 'jpn+eng', {
                logger: () => { },
                tessedit_pageseg_mode: '3',
            });
            const confidence = data.confidence ?? 0;
            if (confidence > bestConfidence) {
                bestConfidence = confidence;
                bestPath = candidatePath;
                bestAngles = [angle];
            }
            else if (confidence === bestConfidence) {
                bestAngles.push(angle);
            }
        }
        catch (error) {
            console.warn(`回転 ${angle}° の試行でエラー:`, error);
        }
    }
    console.log('回転評価結果:', { bestAngles, bestConfidence });
    return { bestPath, bestAngles, cleanup: cleanupPaths };
}
async function tryExtractTextLayer(reader, pageLimit = 20) {
    let combined = '';
    for (let pageNum = 1; pageNum <= Math.min(reader.numPages, pageLimit); pageNum++) {
        try {
            const page = await reader.getPage(pageNum);
            const textContent = await page.getTextContent();
            const pageText = textContent.items
                .map((item) => (item.str && item.str.trim() ? item.str.trim() : ''))
                .filter(Boolean)
                .join(' ');
            if (pageText) {
                combined += `=== ページ ${pageNum} ===\n${pageText}\n\n`;
            }
        }
        catch (error) {
            console.warn(`PDFテキスト層抽出エラー (ページ ${pageNum}):`, error);
        }
    }
    return combined.trim();
}
class SimpleCanvasFactory {
    create(width, height) {
        const canvas = createCanvas(width, height);
        const context = canvas.getContext('2d');
        return { canvas, context };
    }
    reset(canvasAndContext, width, height) {
        canvasAndContext.canvas.width = width;
        canvasAndContext.canvas.height = height;
    }
    destroy(canvasAndContext) {
        canvasAndContext.canvas = null;
        canvasAndContext.context = null;
    }
}
async function renderPdfPageToBuffer(reader, pageNum, dpi = 400) {
    try {
        const page = await reader.getPage(pageNum);
        const scale = dpi / 72;
        const scaledViewport = page.getViewport({ scale });
        const canvasFactory = new SimpleCanvasFactory();
        const { canvas, context } = canvasFactory.create(Math.ceil(scaledViewport.width), Math.ceil(scaledViewport.height));
        const renderContext = {
            canvasContext: context,
            viewport: scaledViewport,
            canvasFactory,
        };
        await page.render(renderContext).promise;
        return canvas.toBuffer();
    }
    catch (error) {
        console.warn(`PDFページレンダリングエラー (ページ ${pageNum}):`, error);
        return null;
    }
}
async function ocrPdfPages(reader, jobId, pageLimit = 5) {
    let combined = '';
    for (let pageNum = 1; pageNum <= Math.min(reader.numPages, pageLimit); pageNum++) {
        const rendered = await renderPdfPageToBuffer(reader, pageNum);
        if (!rendered) {
            continue;
        }
        const tempImagePath = createTempFilePath(`pdf_page_${pageNum}`);
        fs.writeFileSync(tempImagePath, rendered);
        try {
            const text = await extractTextFromImage(tempImagePath, jobId);
            if (text && text.trim().length > 0) {
                combined += `=== ページ ${pageNum} (OCR) ===\n${text}\n\n`;
            }
        }
        finally {
            cleanupTempFiles([tempImagePath]);
        }
        updateJobProgress(jobId, 70 + (pageNum / Math.min(reader.numPages, pageLimit)) * 20);
    }
    return combined.trim();
}
async function extractTextFromPDF(filePath, enableOCR = false, jobId) {
    try {
        const pdfBytes = fs.readFileSync(filePath);
        const pdfDoc = await PDFDocument.load(pdfBytes);
        const loadingTask = pdfjsLib.getDocument({
            data: pdfBytes,
            useSystemFonts: true,
        });
        const reader = await loadingTask.promise;
        let extractedText = await tryExtractTextLayer(reader);
        if (!extractedText && enableOCR) {
            console.log('PDFテキスト層抽出に失敗、OCRを実行します');
            extractedText = await ocrPdfPages(reader, jobId);
        }
        if (!extractedText) {
            extractedText = '[テキストが抽出できませんでした]';
        }
        const stats = fs.statSync(filePath);
        const pages = pdfDoc.getPages();
        const pageCount = pages.length;
        const pageSizes = pages.map(page => {
            const { width, height } = page.getSize();
            return `${Math.round(width)}×${Math.round(height)}px`;
        });
        const analysisResult = `
=== PDF分析結果 ===
ファイル名: ${path.basename(filePath)}
ファイルサイズ: ${Math.round(stats.size / 1024)}KB
ページ数: ${pageCount}ページ
ページサイズ: ${pageSizes.join(', ')}

=== 抽出されたテキスト ===
${extractedText}

=== メタデータ ===
タイトル: ${pdfDoc.getTitle() || '不明'}
作成者: ${pdfDoc.getAuthor() || '不明'}
件名: ${pdfDoc.getSubject() || '不明'}

`.trim();
        return analysisResult;
    }
    catch (error) {
        console.error('PDF抽出エラー:', error);
        if (enableOCR) {
            try {
                const fallback = await extractTextFromPDFWithOCR(filePath, jobId);
                if (fallback) {
                    return fallback;
                }
            }
            catch (ocrError) {
                console.warn('PDF OCRフォールバックでエラー:', ocrError);
            }
        }
        throw error;
    }
}
const tesseractLogger = (jobId, baseProgress = 0, span = 40) => (m) => {
    if (jobId && m.status === 'recognizing text' && typeof m.progress === 'number') {
        updateJobProgress(jobId, baseProgress + m.progress * span);
    }
};
async function recognizeText(imagePath, lang, options) {
    const baseOptions = options;
    return Tesseract.recognize(imagePath, lang, baseOptions);
}
function getGlobalDocuments() {
    if (!global.documents) {
        global.documents = new Map();
    }
    return global.documents;
}
function getGlobalJobs() {
    if (!global.jobs) {
        global.jobs = new Map();
    }
    return global.jobs;
}
function getGlobalHybridResults() {
    if (!global.hybridResults) {
        global.hybridResults = new Map();
    }
    return global.hybridResults;
}
function findActiveSegments(values, minActive, minLength) {
    const segments = [];
    let start = -1;
    let score = 0;
    for (let i = 0; i < values.length; i++) {
        const value = values[i];
        if (value >= minActive) {
            if (start === -1) {
                start = i;
                score = value;
            }
            else {
                score += value;
            }
        }
        else if (start !== -1) {
            const end = i - 1;
            if (end - start + 1 >= minLength) {
                segments.push({ start, end, score });
            }
            start = -1;
            score = 0;
        }
    }
    if (start !== -1) {
        const end = values.length - 1;
        if (end - start + 1 >= minLength) {
            segments.push({ start, end, score });
        }
    }
    return segments;
}
function determineBlockPSM(width, height) {
    if (height <= 60) {
        return { type: 'line', psm: '7' };
    }
    const aspect = width / Math.max(height, 1);
    if (aspect >= 2.2) {
        return { type: 'paragraph', psm: '6' };
    }
    if (aspect <= 0.7) {
        return { type: 'block', psm: '5' };
    }
    return { type: 'block', psm: '4' };
}
async function detectLayoutBlocks(binaryImagePath) {
    try {
        const { data, info } = await sharp(binaryImagePath)
            .grayscale()
            .threshold(128)
            .raw()
            .toBuffer({ resolveWithObject: true });
        const { width, height, channels } = info;
        const horizontalProjection = new Array(height).fill(0);
        for (let y = 0; y < height; y++) {
            let rowSum = 0;
            for (let x = 0; x < width; x++) {
                const idx = (y * width + x) * channels;
                if (data[idx] < 200) {
                    rowSum++;
                }
            }
            horizontalProjection[y] = rowSum;
        }
        const rowThreshold = Math.max(8, Math.floor(width * 0.015));
        const rowSegments = findActiveSegments(horizontalProjection, rowThreshold, Math.max(6, Math.floor(height * 0.01)));
        const blocks = [];
        for (const segment of rowSegments) {
            const segTop = Math.max(0, segment.start - 1);
            const segBottom = Math.min(height - 1, segment.end + 1);
            const segmentHeight = segBottom - segTop + 1;
            const verticalProjection = new Array(width).fill(0);
            for (let x = 0; x < width; x++) {
                let colSum = 0;
                for (let y = segTop; y <= segBottom; y++) {
                    const idx = (y * width + x) * channels;
                    if (data[idx] < 200) {
                        colSum++;
                    }
                }
                verticalProjection[x] = colSum;
            }
            const columnThreshold = Math.max(6, Math.floor(segmentHeight * 0.02));
            const columnSegments = findActiveSegments(verticalProjection, columnThreshold, Math.max(12, Math.floor(width * 0.03)));
            const segmentsToUse = columnSegments.length > 0 ? columnSegments : [{ start: 0, end: width - 1, score: segmentHeight * width }];
            for (const column of segmentsToUse) {
                const left = Math.max(0, column.start - 1);
                const right = Math.min(width - 1, column.end + 1);
                const blockWidth = right - left + 1;
                const blockHeight = segmentHeight;
                if (blockWidth < Math.max(16, Math.floor(width * 0.015)) || blockHeight < Math.max(12, Math.floor(height * 0.01))) {
                    continue;
                }
                const { type, psm } = determineBlockPSM(blockWidth, blockHeight);
                const area = blockWidth * blockHeight;
                const density = area > 0 ? column.score / area : 0;
                blocks.push({
                    left,
                    top: segTop,
                    width: blockWidth,
                    height: blockHeight,
                    type,
                    psm,
                    score: density,
                });
            }
        }
        return { width, height, blocks };
    }
    catch (error) {
        console.warn('レイアウト解析に失敗しました:', error);
        return { width: 0, height: 0, blocks: [] };
    }
}
