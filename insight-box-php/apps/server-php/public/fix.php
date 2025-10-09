<?php
/**
 * 緊急修正スクリプト
 * storage/framework/views/ の書き込み権限を修正
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>権限修正</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">🔧 権限修正スクリプト</h1>';

$basePath = realpath(__DIR__ . '/..');
$results = [];

// 修正が必要なディレクトリ
$directories = [
    'storage/framework/views',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/logs',
    'bootstrap/cache',
];

echo '<div class="space-y-4">';

foreach ($directories as $dir) {
    $fullPath = $basePath . '/' . $dir;
    
    echo '<div class="p-4 border rounded">';
    echo '<p class="font-medium text-gray-900">' . htmlspecialchars($dir) . '</p>';
    
    if (!file_exists($fullPath)) {
        echo '<p class="text-sm text-red-600">❌ ディレクトリが存在しません</p>';
        
        if (mkdir($fullPath, 0777, true)) {
            chmod($fullPath, 0777);
            echo '<p class="text-sm text-green-600">✅ ディレクトリを作成しました (0777)</p>';
        } else {
            echo '<p class="text-sm text-red-600">❌ ディレクトリの作成に失敗</p>';
        }
    } else {
        $currentPerms = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo '<p class="text-sm text-gray-600">現在のパーミッション: ' . $currentPerms . '</p>';
        
        if (chmod($fullPath, 0777)) {
            echo '<p class="text-sm text-green-600">✅ パーミッションを 0777 に変更しました</p>';
        } else {
            echo '<p class="text-sm text-yellow-600">⚠️ パーミッション変更に失敗（手動で設定してください）</p>';
        }
    }
    
    echo '</div>';
}

// キャッシュクリア
echo '<div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded">';
echo '<h2 class="font-semibold text-blue-900 mb-2">🗑️ キャッシュクリア</h2>';

$cacheFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/events.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/services.php',
];

foreach ($cacheFiles as $file) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo '<p class="text-sm text-green-600">✅ ' . htmlspecialchars($file) . ' を削除</p>';
        } else {
            echo '<p class="text-sm text-yellow-600">⚠️ ' . htmlspecialchars($file) . ' の削除に失敗</p>';
        }
    }
}

// storage/framework/views/ のコンパイル済みビューを削除
$viewsPath = $basePath . '/storage/framework/views/';
if (file_exists($viewsPath)) {
    $files = glob($viewsPath . '*.php');
    $deletedCount = 0;
    foreach ($files as $file) {
        if (unlink($file)) {
            $deletedCount++;
        }
    }
    echo '<p class="text-sm text-green-600">✅ コンパイル済みビューを ' . $deletedCount . ' 件削除</p>';
}

echo '</div>';

echo '<div class="mt-6 p-4 bg-green-50 border border-green-200 rounded">';
echo '<p class="text-green-900 font-semibold">✅ 修正が完了しました！</p>';
echo '<p class="text-sm text-green-800 mt-2">下記のボタンからInsight-Boxを開いてください。</p>';
echo '<a href="/cards" class="mt-4 inline-block px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
        カード一覧を開く
      </a>';
echo '</div>';

echo '<div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">';
echo '<p class="text-yellow-900 font-semibold">⚠️ セキュリティ上の注意</p>';
echo '<p class="text-sm text-yellow-800 mt-2">このスクリプトは修正後に削除することをお勧めします：</p>';
echo '<code class="block mt-2 p-2 bg-yellow-100 text-xs">public/fix.php を削除</code>';
echo '</div>';

echo '</div>';
echo '</div>
</body>
</html>';
