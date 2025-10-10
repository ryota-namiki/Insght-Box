<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    public function imageToText(string $imagePath, string $lang = 'jpn+eng'): string
    {
        $ocr = (new TesseractOCR($imagePath))
            ->lang(...explode('+', $lang))
            ->oem(1)
            ->psm(3);
        
        // Tesseractのフルパスを指定（サーバー環境用）
        if (file_exists('/usr/bin/tesseract')) {
            $ocr->executable('/usr/bin/tesseract');
        }
        
        return $ocr->run();
    }

    public function imageOrTextToText(string $path, string $lang): string
    {
        if (preg_match('/\.txt$/i', $path)) {
            return file_get_contents($path) ?: '';
        }
        return $this->imageToText($path, $lang);
    }
}
