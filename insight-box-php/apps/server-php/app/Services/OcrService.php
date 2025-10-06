<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    public function imageToText(string $imagePath, string $lang = 'jpn+eng'): string
    {
        return (new TesseractOCR($imagePath))
            ->lang(...explode('+', $lang))
            ->oem(1)
            ->psm(3)
            ->run();
    }

    public function imageOrTextToText(string $path, string $lang): string
    {
        if (preg_match('/\.txt$/i', $path)) {
            return file_get_contents($path) ?: '';
        }
        return $this->imageToText($path, $lang);
    }
}
