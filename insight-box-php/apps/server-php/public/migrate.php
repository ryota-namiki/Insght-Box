<?php
/**
 * 本番環境用マイグレーション実行スクリプト
 * 使用後は必ずこのファイルを削除してください
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h1>🔄 マイグレーション実行</h1>";
echo "<pre>";

try {
    // マイグレーション実行
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    echo "\n✅ マイグレーション完了\n";
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
echo "<p><strong>⚠️ 警告: 使用後はこのファイル（migrate.php）を必ず削除してください！</strong></p>";
