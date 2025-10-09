<?php

namespace App\Services;

use OpenAI;

class AiSummaryService
{
    private $client;
    
    public function __construct()
    {
        $apiKey = env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('OPENAI_API_KEY is not set in .env file');
        }
        
        $this->client = OpenAI::client($apiKey);
    }
    
    /**
     * テキストを300字以内に要約
     *
     * @param string $text 要約するテキスト
     * @return string 要約されたテキスト
     */
    public function summarize(string $text): string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini', // コスト効率の良いモデル
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'あなたは優秀な要約アシスタントです。与えられたテキストを必ず300文字以内で要約してください。300文字を超えてはいけません。重要なポイントのみを抽出し、簡潔で読みやすい日本語で返してください。'
                    ],
                    [
                        'role' => 'user',
                        'content' => "以下のテキストを厳密に300文字以内で要約してください。絶対に300文字を超えないでください：\n\n{$text}"
                    ]
                ],
                'max_tokens' => 500, // 少し減らして確実に300文字以内に
                'temperature' => 0.2, // より安定した出力のため低めに
            ]);
            
            $summary = $response->choices[0]->message->content;
            
            // 確実に300文字以内に収める
            if (mb_strlen($summary) > 300) {
                $summary = mb_substr($summary, 0, 300);
                // 最後の文を途中で切らないように、句点で終わるように調整
                $lastPeriod = mb_strrpos($summary, '。');
                if ($lastPeriod !== false && $lastPeriod > 250) {
                    $summary = mb_substr($summary, 0, $lastPeriod + 1);
                }
            }
            
            return trim($summary);
            
        } catch (\Exception $e) {
            \Log::error('AI要約エラー: ' . $e->getMessage());
            
            // エラー時はテキストの最初の300字を返す
            return mb_substr($text, 0, 297) . '...';
        }
    }
    
    /**
     * Webクリップの内容を要約（タイトル + 本文）
     *
     * @param string $title タイトル
     * @param string $content 本文
     * @return string 要約されたテキスト
     */
    public function summarizeWebClip(string $title, string $content): string
    {
        $fullText = "タイトル: {$title}\n\n内容: {$content}";
        return $this->summarize($fullText);
    }
}

