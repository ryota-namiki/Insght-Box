<?php

/**
 * マイグレーション実行スクリプト
 * セキュリティのため、実行後はこのファイルを削除してください
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイグレーション実行</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">🚀 マイグレーション実行</h1>
        <div class="space-y-4">';

try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo '<p class="text-green-700">✅ アプリケーション起動成功</p>';
    
    // マイグレーションを実行
    $exitCode = Artisan::call('migrate', [
        '--force' => true,
    ]);
    
    $output = Artisan::output();
    
    if ($exitCode === 0) {
        echo '<div class="mt-6 p-4 bg-green-50 border border-green-200 rounded">
                <p class="text-green-900 font-semibold">🎉 マイグレーション成功！</p>
                <pre class="mt-3 text-sm bg-gray-900 text-green-400 p-3 rounded overflow-x-auto">' . htmlspecialchars($output) . '</pre>
                <div class="mt-4">
                    <a href="/boards" class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        ボード一覧を開く
                    </a>
                </div>
              </div>';
        
        echo '<div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-yellow-900 font-semibold">⚠️ セキュリティ警告</p>
                <p class="text-yellow-800 mt-2">マイグレーション完了後、このファイル（run-migrations.php）を削除してください。</p>
              </div>';
    } else {
        echo '<div class="mt-6 p-4 bg-red-50 border border-red-200 rounded">
                <p class="text-red-900 font-semibold">❌ マイグレーション失敗</p>
                <pre class="mt-3 text-sm bg-gray-900 text-red-400 p-3 rounded overflow-x-auto">' . htmlspecialchars($output) . '</pre>
              </div>';
    }
    
} catch (Exception $e) {
    echo '<div class="mt-6 p-4 bg-red-50 border border-red-200 rounded">
            <h2 class="text-red-900 font-semibold mb-2">❌ エラー発生</h2>
            <p class="text-red-800 mb-3">' . htmlspecialchars($e->getMessage()) . '</p>
            <div class="bg-gray-900 text-red-400 p-3 rounded text-xs overflow-x-auto">
                <p><strong>ファイル:</strong> ' . htmlspecialchars($e->getFile()) . '</p>
                <p><strong>行:</strong> ' . $e->getLine() . '</p>
                <pre class="mt-3">' . htmlspecialchars($e->getTraceAsString()) . '</pre>
            </div>
          </div>';
}

echo '    </div>
    </div>
</body>
</html>';

