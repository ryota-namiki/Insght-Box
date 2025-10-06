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
     * テキストを250字以内に要約
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
                        'content' => 'あなたは優秀な要約アシスタントです。与えられたテキストを250字以内で簡潔に要約してください。重要なポイントを押さえ、読みやすい日本語で返してください。'
                    ],
                    [
                        'role' => 'user',
                        'content' => "以下のテキストを250字以内で要約してください：\n\n{$text}"
                    ]
                ],
                'max_tokens' => 500, // 日本語の場合、250字程度で十分
                'temperature' => 0.3, // 安定した要約のため低めに設定
            ]);
            
            $summary = $response->choices[0]->message->content;
            
            // 250字を超える場合はトリミング
            if (mb_strlen($summary) > 250) {
                $summary = mb_substr($summary, 0, 247) . '...';
            }
            
            return trim($summary);
            
        } catch (\Exception $e) {
            \Log::error('AI要約エラー: ' . $e->getMessage());
            
            // エラー時はテキストの最初の250字を返す
            return mb_substr($text, 0, 247) . '...';
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

