<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;

class WebClipService
{
    public function extractMainText(string $html): string
    {
        $crawler = new Crawler($html);
        // タイトル＋見出し＋本文候補を抽出（簡易版）
        $parts = [];
        $title = $crawler->filter('title')->first();
        if ($title->count()) {
            $parts[] = trim($title->text());
        }
        $crawler->filter('h1,h2,h3,p,article')->each(function ($n) use (&$parts) {
            $t = trim($n->text());
            if (mb_strlen($t) >= 10) {
                $parts[] = $t;
            }
        });
        return implode("\n\n", $parts);
    }

    /**
     * Webページからタイトルとメタデータを抽出
     */
    public function extractMetadata(string $html): array
    {
        $crawler = new Crawler($html);
        
        // タイトルを抽出
        $title = '';
        $titleNode = $crawler->filter('title')->first();
        if ($titleNode->count()) {
            $title = trim($titleNode->text());
        }
        
        // メタディスクリプションを抽出
        $description = '';
        $metaDesc = $crawler->filter('meta[name="description"]')->first();
        if ($metaDesc->count()) {
            $description = $metaDesc->attr('content') ?? '';
        }
        
        // OGディスクリプションも試す
        if (empty($description)) {
            $ogDesc = $crawler->filter('meta[property="og:description"]')->first();
            if ($ogDesc->count()) {
                $description = $ogDesc->attr('content') ?? '';
            }
        }
        
        // 本文の最初の数段落から要約を生成（ディスクリプションがない場合）
        if (empty($description)) {
            $paragraphs = [];
            $crawler->filter('p')->each(function ($n) use (&$paragraphs) {
                $text = trim($n->text());
                if (mb_strlen($text) >= 20 && count($paragraphs) < 3) {
                    $paragraphs[] = $text;
                }
            });
            if (!empty($paragraphs)) {
                $description = implode(' ', $paragraphs);
                // 最大500文字に制限
                if (mb_strlen($description) > 500) {
                    $description = mb_substr($description, 0, 500) . '...';
                }
            }
        }
        
        return [
            'title' => $title,
            'description' => trim($description),
        ];
    }
}
