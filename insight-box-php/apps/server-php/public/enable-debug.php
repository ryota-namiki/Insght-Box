<?php
/**
 * デバッグモード有効化スクリプト
 * エラーの詳細を表示できるようにします
 */

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    die('❌ .env ファイルが見つかりません');
}

$envContent = file_get_contents($envPath);

// APP_DEBUG=true に変更
$envContent = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=true', $envContent);

// APP_ENV=local に変更（詳細なエラー表示のため）
$envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=local', $envContent);

file_put_contents($envPath, $envContent);

// キャッシュクリア
$cacheFiles = [
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../bootstrap/cache/routes-v7.php',
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        @unlink($file);
    }
}

echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>デバッグモード有効化</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-4">🔍 デバッグモード有効化</h1>
        <div class="bg-green-50 border border-green-200 rounded p-4 mb-6">
            <p class="text-green-800">✅ デバッグモードを有効にしました</p>
            <p class="text-green-800">✅ キャッシュをクリアしました</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
            <p class="text-yellow-800 text-sm">
                これで詳細なエラーメッセージが表示されます。<br>
                エラー確認後は、必ずデバッグモードを無効にしてください。
            </p>
        </div>
        <div class="space-y-3">
            <a href="/" class="block text-center px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Insight-Boxを開く（詳細エラー表示）
            </a>
            <p class="text-sm text-gray-600 text-center">
                エラーの詳細が赤い画面で表示されます
            </p>
        </div>
    </div>
</body>
</html>';

