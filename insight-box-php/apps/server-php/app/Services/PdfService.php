<?php

namespace App\Services;

class PdfService
{
    /** @return string[] image paths */
    public function pdfToImages(string $pdfPath): array
    {
        // Imagick があればこちら
        if (extension_loaded('imagick')) {
            $im = new \Imagick();
            $im->setResolution(300, 300);
            $im->readImage($pdfPath);
            $out = [];
            foreach ($im as $i => $page) {
                $page->setImageFormat('png');
                $tmp = sys_get_temp_dir() . "/ocr_page_{$i}_" . uniqid() . ".png";
                $page->writeImage($tmp);
                $out[] = $tmp;
            }
            $im->clear();
            $im->destroy();
            return $out;
        }
        // なければ pdftoppm
        $dir = sys_get_temp_dir() . "/ocr_" . uniqid();
        @mkdir($dir);
        $base = $dir . '/page';
        $cmd = sprintf('pdftoppm -png -r 300 %s %s', escapeshellarg($pdfPath), escapeshellarg($base));
        exec($cmd);
        return glob("$base-*.png") ?: [];
    }
}
