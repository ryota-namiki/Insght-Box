<?php
/**
 * キャッシュクリアスクリプト
 * 500エラーが出た場合、このスクリプトでキャッシュをクリアできます
 * 
 * アクセス: https://yourdomain.com/fix.php
 * 
 * 実行後は削除してください
 */

require __DIR__ . '/../vendor/autoload.php';

echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>キャッシュクリア</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6">🔧 キャッシュクリア＆修正</h1>
            <div class="space-y-3">';

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    echo '<p class="text-green-700">✅ Laravel起動成功</p>';
    
    // キャッシュクリア
    $kernel->call('config:clear');
    echo '<p class="text-green-700">✅ config:clear 実行</p>';
    
    $kernel->call('route:clear');
    echo '<p class="text-green-700">✅ route:clear 実行</p>';
    
    $kernel->call('view:clear');
    echo '<p class="text-green-700">✅ view:clear 実行</p>';
    
    $kernel->call('cache:clear');
    echo '<p class="text-green-700">✅ cache:clear 実行</p>';
    
    // ストレージディレクトリ作成
    $storageDirs = [
        storage_path('app/data'),
        storage_path('framework/cache/data'),
        storage_path('framework/sessions'),
        storage_path('framework/views'),
        storage_path('logs'),
    ];
    
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo '<p class="text-blue-700">📁 作成: ' . basename($dir) . '</p>';
        }
    }
    
    // デフォルトイベントを作成
    $eventsJsonPath = storage_path('app/data/events.json');
    if (!file_exists($eventsJsonPath)) {
        $defaultEvents = [
            'dd35200f-c22f-460a-adaf-597acba70bdc' => [
                'id' => 'dd35200f-c22f-460a-adaf-597acba70bdc',
                'name' => 'デフォルトイベント',
                'startDate' => date('Y-m-d'),
                'endDate' => date('Y-m-d', strtotime('+30 days')),
                'location' => null,
                'created_at' => date('c'),
                'updated_at' => date('c'),
            ]
        ];
        file_put_contents($eventsJsonPath, json_encode($defaultEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @chmod($eventsJsonPath, 0666);
        echo '<p class="text-green-700">✅ デフォルトイベントを作成しました</p>';
    }
    
    echo '</div>
            <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded">
                <p class="text-green-900 font-semibold mb-2">🎉 修正完了！</p>
                <p class="text-green-800 text-sm mb-4">キャッシュをクリアし、必要なファイルを作成しました。</p>
                <a href="/" class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Insight-Boxを開く →
                </a>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-yellow-800 text-sm">⚠️ このファイル（fix.php）を削除してください</p>
            </div>
        </div>
    </div>
</body>
</html>';
    
} catch (Exception $e) {
    echo '<p class="text-red-700">❌ エラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>
            <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded">
                <p class="text-red-900 font-semibold mb-2">エラーが発生しました</p>
                <p class="text-red-800 text-sm">' . htmlspecialchars($e->getMessage()) . '</p>
                <pre class="mt-3 text-xs bg-gray-900 text-red-400 p-3 rounded overflow-x-auto">' . htmlspecialchars($e->getTraceAsString()) . '</pre>
            </div>
        </div>
    </div>
</body>
</html>';
}

