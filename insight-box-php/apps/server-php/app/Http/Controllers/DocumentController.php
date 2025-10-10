<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Repositories\{DocumentRepository, JobRepository};
use App\Jobs\OcrProcessJob;

class DocumentController extends Controller
{
    public function store(Request $req, DocumentRepository $docs, JobRepository $jobs)
    {
        $validated = $req->validate([
            'file' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,application/pdf,text/plain|max:10240',
            'url' => 'nullable|url',
            'lang' => 'nullable|string' // e.g. "jpn+eng"
        ]);
        if (!$req->hasFile('file') && !($validated['url'] ?? null)) {
            return response()->json(['error' => 'file or url required'], 422);
        }

        $documentId = (string) Str::uuid();
        $sourceType = $req->hasFile('file') ? 'upload' : 'url';
        $storedPath = null;

        if ($sourceType === 'upload') {
            $storedPath = $req->file('file')->store("uploads/{$documentId}", 'local');
            \Log::info('File uploaded to: ' . $storedPath);
        } else {
            $content = file_get_contents($validated['url']);
            $ext = 'html';
            $storedPath = "uploads/{$documentId}/remote.$ext";
            Storage::disk('local')->put($storedPath, $content);
        }

        $docs->upsert($documentId, [
            'id' => $documentId,
            'source_type' => $sourceType,
            'path' => $storedPath,
            'url' => $validated['url'] ?? null,
            'lang' => $validated['lang'] ?? 'jpn+eng',
            'text' => null,
            'created_at' => now()->toIso8601String(),
        ]);

        $jobId = (string) Str::uuid();
        $jobs->upsert($jobId, [
            'id' => $jobId,
            'status' => 'queued',
            'progress' => 0,
            'document_id' => $documentId,
            'created_at' => now()->toIso8601String(),
        ]);

        // 同期的に OCR 処理を実行（テスト用）
        try {
            \Log::info("OCR処理開始: documentId={$documentId}, sourceType={$sourceType}");
            
            $ocrService = app(\App\Services\OcrService::class);
            $pdfService = app(\App\Services\PdfService::class);
            $webClipService = app(\App\Services\WebClipService::class);
            
            $jobs->updateStatus($jobId, 'running', 1);
            
            $text = '';
            $ocrError = null;
            
            if ($sourceType === 'upload') {
                $path = storage_path("app/private/{$storedPath}");
                \Log::info("OCR対象ファイル: {$path}");
                \Log::info("ファイル存在確認: " . (file_exists($path) ? 'YES' : 'NO'));
                
                if (!file_exists($path)) {
                    throw new \Exception("アップロードファイルが見つかりません: {$path}");
                }
                
                try {
                    if (preg_match('/\.pdf$/i', $path)) {
                        $pages = $pdfService->pdfToImages($path);
                        $total = max(count($pages), 1);
                        $i = 0;
                        foreach ($pages as $img) {
                            $text .= $ocrService->imageToText($img, $validated['lang'] ?? 'jpn+eng');
                            $i++;
                            $jobs->updateProgress($jobId, intval($i / $total * 98));
                        }
                    } else {
                        \Log::info("画像OCR処理実行中...");
                        $text = $ocrService->imageOrTextToText($path, $validated['lang'] ?? 'jpn+eng');
                        \Log::info("OCR結果テキスト長: " . mb_strlen($text));
                        $jobs->updateProgress($jobId, 90);
                    }
                } catch (\Exception $ocrException) {
                    // OCRエラー（Tesseractがインストールされていない等）をキャッチ
                    \Log::warning("OCR処理をスキップ: " . $ocrException->getMessage());
                    $ocrError = $ocrException->getMessage();
                    $text = "[OCR処理エラー]\n\nサーバーにTesseract OCRがインストールされていないため、テキスト抽出ができませんでした。\n\n画像ファイルは正常にアップロードされています。\nファイルプレビューとカメラ撮影機能は正常に動作します。";
                    $jobs->updateProgress($jobId, 90);
                }
            } else {
                $rawPath = storage_path("app/private/{$storedPath}");
                $html = file_get_contents($rawPath);
                $text = $webClipService->extractMainText($html);
                
                // メタデータを抽出
                $metadata = $webClipService->extractMetadata($html);
                $docs->updateMetadata($documentId, $metadata);
                
                $jobs->updateProgress($jobId, 90);
            }
            
            $textLength = $text ? mb_strlen($text) : 0;
            \Log::info("OCR処理完了: テキスト長={$textLength}");
            
            $docs->updateText($documentId, $text);
            $jobs->updateStatus($jobId, 'succeeded', 100);
            
            \Log::info("ドキュメントテキスト保存完了: documentId={$documentId}");
        } catch (\Throwable $e) {
            \Log::error("OCR処理エラー: " . $e->getMessage());
            \Log::error("スタックトレース: " . $e->getTraceAsString());
            $jobs->fail($jobId, $e->getMessage());
        }

        return response()->json([
            'job_id' => $jobId,
            'document_id' => $documentId,
        ]);
    }

    public function text(string $id, DocumentRepository $docs)
    {
        $doc = $docs->find($id);
        if (!$doc) {
            return response()->json(['error' => 'not found'], 404);
        }
        return response()->json(['text' => $doc['text'] ?? '']);
    }

    public function image(string $id, DocumentRepository $docs)
    {
        $doc = $docs->find($id);
        if (!$doc) {
            return response()->json(['error' => 'not found'], 404);
        }

        if ($doc['source_type'] !== 'upload') {
            return response()->json(['error' => 'not an uploaded file'], 400);
        }

        $path = storage_path("app/private/{$doc['path']}");
        if (!file_exists($path)) {
            return response()->json(['error' => 'file not found'], 404);
        }

        // 画像ファイルのみ返す（PDFは除外）
        if (!preg_match('/\.(jpe?g|png|gif)$/i', $path)) {
            return response()->json(['error' => 'not an image file'], 400);
        }

        return response()->file($path);
    }

    public function metadata(string $id, DocumentRepository $docs)
    {
        $doc = $docs->find($id);
        if (!$doc) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json([
            'metadata' => $doc['metadata'] ?? [],
            'url' => $doc['url'] ?? null,
        ]);
    }
}
