<?php
/**
 * 緊急修正スクリプト（改訂版）
 * データベースとストレージの権限を最優先で修正
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
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">🔧 権限修正スクリプト（改訂版）</h1>';

$basePath = realpath(__DIR__ . '/..');

echo '<div class="space-y-4">';

// ステップ1: データベースファイルの権限修正
echo '<div class="p-4 border-2 border-blue-500 rounded">';
echo '<h2 class="font-semibold text-blue-900 mb-2">📁 ステップ1: データベースファイル</h2>';

$dbPath = $basePath . '/database/database.sqlite';
if (file_exists($dbPath)) {
    $currentPerms = substr(sprintf('%o', fileperms($dbPath)), -4);
    echo '<p class="text-sm text-gray-600">現在: ' . $currentPerms . '</p>';
    
    if (@chmod($dbPath, 0666)) {
        echo '<p class="text-sm text-green-600">✅ database.sqlite を 0666 に変更</p>';
    } else {
        echo '<p class="text-sm text-red-600">❌ 変更失敗</p>';
    }
} else {
    echo '<p class="text-sm text-yellow-600">⚠️ database.sqlite が見つかりません</p>';
}

// database/ ディレクトリ自体
$dbDir = $basePath . '/database';
if (@chmod($dbDir, 0777)) {
    echo '<p class="text-sm text-green-600">✅ database/ を 0777 に変更</p>';
}

echo '</div>';

// ステップ2: キャッシュファイルを直接削除
echo '<div class="p-4 border-2 border-red-500 rounded">';
echo '<h2 class="font-semibold text-red-900 mb-2">🗑️ ステップ2: キャッシュ削除</h2>';

$cachePaths = [
    '/bootstrap/cache',
    '/storage/framework/cache',
    '/storage/framework/views',
];

$deletedTotal = 0;

foreach ($cachePaths as $cachePath) {
    $fullPath = $basePath . $cachePath;
    if (file_exists($fullPath)) {
        $files = glob($fullPath . '/*.php');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        
        $deletedTotal += $deleted;
        
        if ($deleted > 0) {
            echo '<p class="text-sm text-green-600">✅ ' . htmlspecialchars($cachePath) . ': ' . $deleted . '件削除</p>';
        } else {
            echo '<p class="text-sm text-gray-600">📄 ' . htmlspecialchars($cachePath) . ': ファイルなし</p>';
        }
    }
}

echo '<p class="text-sm font-semibold text-green-700 mt-2">合計: ' . $deletedTotal . '件のキャッシュファイルを削除</p>';

echo '</div>';

// ステップ3: Artisanコマンドでキャッシュクリア
echo '<div class="p-4 border-2 border-purple-500 rounded">';
echo '<h2 class="font-semibold text-purple-900 mb-2">⚙️ ステップ3: Artisan キャッシュクリア</h2>';

try {
    // config:clear
    @exec('cd ' . escapeshellarg($basePath) . ' && php artisan config:clear 2>&1', $output1, $ret1);
    if ($ret1 === 0) {
        echo '<p class="text-sm text-green-600">✅ config:clear 成功</p>';
    } else {
        echo '<p class="text-sm text-yellow-600">⚠️ config:clear: ' . htmlspecialchars(implode(' ', $output1)) . '</p>';
    }
    
    // view:clear
    @exec('cd ' . escapeshellarg($basePath) . ' && php artisan view:clear 2>&1', $output2, $ret2);
    if ($ret2 === 0) {
        echo '<p class="text-sm text-green-600">✅ view:clear 成功</p>';
    } else {
        echo '<p class="text-sm text-yellow-600">⚠️ view:clear: ' . htmlspecialchars(implode(' ', $output2)) . '</p>';
    }
    
    // cache:clear
    @exec('cd ' . escapeshellarg($basePath) . ' && php artisan cache:clear 2>&1', $output3, $ret3);
    if ($ret3 === 0) {
        echo '<p class="text-sm text-green-600">✅ cache:clear 成功</p>';
    } else {
        echo '<p class="text-sm text-yellow-600">⚠️ cache:clear: ' . htmlspecialchars(implode(' ', $output3)) . '</p>';
    }
    
} catch (Exception $e) {
    echo '<p class="text-sm text-yellow-600">⚠️ Artisanコマンド実行失敗: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

// ステップ4: イベント作成（存在しない場合）
echo '<div class="p-4 border-2 border-green-500 rounded">';
echo '<h2 class="font-semibold text-green-900 mb-2">📋 ステップ4: デフォルトイベント確認</h2>';

try {
    require_once $basePath . '/vendor/autoload.php';
    $app = require_once $basePath . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $eventExists = DB::table('events')->where('id', 'default-event')->exists();
    
    if (!$eventExists) {
        DB::table('events')->insert([
            'id' => 'default-event',
            'name' => 'デフォルトイベント',
            'description' => 'システム作成',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo '<p class="text-sm text-green-600">✅ デフォルトイベントを作成しました</p>';
    } else {
        echo '<p class="text-sm text-blue-600">ℹ️ デフォルトイベントは既に存在します</p>';
    }
} catch (Exception $e) {
    echo '<p class="text-sm text-yellow-600">⚠️ イベント確認失敗: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

echo '<div class="mt-6 p-4 bg-green-50 border-2 border-green-500 rounded">';
echo '<p class="text-green-900 font-bold text-lg">✅ 修正処理が完了しました！</p>';
echo '<p class="text-sm text-green-800 mt-2">下記のボタンからInsight-Boxを開いてください。</p>';
echo '<a href="/cards" class="mt-4 inline-block px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-semibold">
        🚀 カード一覧を開く
      </a>';
echo '</div>';

echo '<div class="mt-6 p-4 bg-red-50 border-2 border-red-300 rounded">';
echo '<p class="text-red-900 font-semibold">🚨 それでもエラーが出る場合</p>';
echo '<ol class="list-decimal list-inside text-sm text-red-800 mt-2 space-y-1">';
echo '<li>FileZillaで以下のディレクトリを右クリック → ファイルのパーミッション → 777（再帰的に）:</li>';
echo '<ul class="list-disc list-inside ml-6 space-y-1">';
echo '<li><code>storage/</code></li>';
echo '<li><code>bootstrap/cache/</code></li>';
echo '<li><code>database/</code></li>';
echo '</ul>';
echo '<li>この <code>fix.php</code> を再実行</li>';
echo '</ol>';
echo '</div>';

echo '</div>';
echo '</div>
</body>
</html>';
