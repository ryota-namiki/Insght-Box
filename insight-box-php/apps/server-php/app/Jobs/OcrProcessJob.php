<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Repositories\{JobRepository, DocumentRepository};
use App\Services\{OcrService, PdfService, WebClipService};

class OcrProcessJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $jobId)
    {
    }

    public function handle(
        JobRepository $jobs,
        DocumentRepository $docs,
        OcrService $ocr,
        PdfService $pdf,
        WebClipService $clip
    ): void {
        $job = $jobs->find($this->jobId);
        if (!$job) {
            return;
        }

        $jobs->updateStatus($this->jobId, 'running', 1);
        $docId = $job['document_id'];
        $doc = $docs->find($docId);

        try {
            $text = '';
            if ($doc['source_type'] === 'upload') {
                $path = storage_path("app/{$doc['path']}");
                if (preg_match('/\.pdf$/i', $path)) {
                    $pages = $pdf->pdfToImages($path); // array of image paths
                    $total = max(count($pages), 1);
                    $i = 0;
                    foreach ($pages as $img) {
                        $text .= $ocr->imageToText($img, $doc['lang']);
                        $i++;
                        $jobs->updateProgress($this->jobId, intval($i / $total * 98));
                    }
                } else {
                    $text = $ocr->imageOrTextToText($path, $doc['lang']);
                    $jobs->updateProgress($this->jobId, 90);
                }
            } else {
                // URL → Web クリップ → テキスト抽出（見出し、本文ブロック抽出）
                $rawPath = storage_path("app/{$doc['path']}");
                $html = file_get_contents($rawPath);
                $text = $clip->extractMainText($html);
                
                // メタデータを抽出
                $metadata = $clip->extractMetadata($html);
                $docs->updateMetadata($docId, $metadata);
                
                $jobs->updateProgress($this->jobId, 90);
            }

            $docs->updateText($docId, $text);
            $jobs->updateStatus($this->jobId, 'succeeded', 100);
        } catch (\Throwable $e) {
            $jobs->fail($this->jobId, $e->getMessage());
        }
    }
}
