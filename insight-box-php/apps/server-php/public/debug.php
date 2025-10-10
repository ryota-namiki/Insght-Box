<?php
/**
 * デバッグスクリプト
 * エラーの詳細を確認するためのツール
 * 
 * アクセス: https://yourdomain.com/debug.php
 * 
 * 確認後は必ず削除してください
 */

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insight-Box デバッグ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6">🔍 Insight-Box デバッグ情報</h1>
            
            <div class="space-y-6">
                <!-- PHP情報 -->
                <div class="bg-blue-50 p-4 rounded">
                    <h2 class="font-semibold mb-2">PHP バージョン</h2>
                    <p class="font-mono text-sm"><?= PHP_VERSION ?></p>
                </div>
                
                <!-- ファイル存在チェック -->
                <div class="bg-gray-50 p-4 rounded">
                    <h2 class="font-semibold mb-3">ファイル・ディレクトリ確認</h2>
                    <div class="space-y-1 text-sm font-mono">
                        <?php
                        $checks = [
                            '.env' => __DIR__ . '/../.env',
                            'vendor/' => __DIR__ . '/../vendor',
                            'bootstrap/app.php' => __DIR__ . '/../bootstrap/app.php',
                            'storage/' => __DIR__ . '/../storage',
                            'database.sqlite' => __DIR__ . '/../database/database.sqlite',
                        ];
                        
                        foreach ($checks as $name => $path) {
                            $exists = file_exists($path);
                            $writable = is_writable($path);
                            echo '<p>';
                            echo $exists ? '✅' : '❌';
                            echo ' ' . htmlspecialchars($name);
                            if ($exists && is_dir($path)) {
                                echo $writable ? ' (書込可)' : ' (書込不可)';
                            }
                            echo '</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- .env 内容確認 -->
                <?php if (file_exists(__DIR__ . '/../.env')): ?>
                <div class="bg-green-50 p-4 rounded">
                    <h2 class="font-semibold mb-3">.env ファイル（機密情報は隠します）</h2>
                    <div class="bg-gray-900 text-green-400 p-3 rounded font-mono text-xs overflow-x-auto">
                        <pre><?php
                        $envContent = file_get_contents(__DIR__ . '/../.env');
                        // パスワードやキーを隠す
                        $envContent = preg_replace('/(PASSWORD|KEY|SECRET)=.*/i', '$1=***HIDDEN***', $envContent);
                        echo htmlspecialchars($envContent);
                        ?></pre>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- エラーログ確認 -->
                <?php
                $logFile = __DIR__ . '/../storage/logs/laravel.log';
                if (file_exists($logFile) && is_readable($logFile)):
                ?>
                <div class="bg-red-50 p-4 rounded">
                    <h2 class="font-semibold mb-3">最新のエラーログ（最後の50行）</h2>
                    <div class="bg-gray-900 text-red-400 p-3 rounded font-mono text-xs overflow-x-auto max-h-96 overflow-y-auto">
                        <pre><?php
                        $lines = file($logFile);
                        $lastLines = array_slice($lines, -50);
                        echo htmlspecialchars(implode('', $lastLines));
                        ?></pre>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Laravel起動テスト -->
                <div class="bg-purple-50 p-4 rounded">
                    <h2 class="font-semibold mb-3">Laravel 起動テスト</h2>
                    <?php
                    try {
                        require __DIR__ . '/../vendor/autoload.php';
                        $app = require_once __DIR__ . '/../bootstrap/app.php';
                        echo '<p class="text-green-700">✅ Laravel が正常に起動します</p>';
                        
                        // データベース接続テスト
                        try {
                            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                            $kernel->bootstrap();
                            
                            $db = $app->make('db');
                            $db->connection()->getPdo();
                            echo '<p class="text-green-700">✅ データベース接続成功</p>';
                            
                            // カード数確認
                            $cardCount = $db->table('cards')->count();
                            echo '<p class="text-green-700">✅ カード数: ' . $cardCount . '件</p>';
                            
                        } catch (Exception $e) {
                            echo '<p class="text-red-700">❌ データベースエラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                        
                    } catch (Exception $e) {
                        echo '<p class="text-red-700">❌ Laravel起動エラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    ?>
                </div>
                
                <!-- 最新のLaravelログ -->
                <div class="bg-yellow-50 p-4 rounded">
                    <h2 class="font-semibold mb-3">📋 最新のログ（最後の100行）</h2>
                    <?php
                    $logPath = __DIR__ . '/../storage/logs/laravel.log';
                    if (file_exists($logPath)) {
                        $lines = file($logPath);
                        $lastLines = array_slice($lines, -100);
                        echo '<pre class="text-xs bg-gray-900 text-gray-100 p-4 rounded overflow-x-auto max-h-96 overflow-y-auto">';
                        foreach ($lastLines as $line) {
                            if (strpos($line, 'ERROR') !== false) {
                                echo '<span class="text-red-400">' . htmlspecialchars($line) . '</span>';
                            } elseif (strpos($line, 'WARNING') !== false) {
                                echo '<span class="text-yellow-400">' . htmlspecialchars($line) . '</span>';
                            } elseif (strpos($line, 'INFO') !== false) {
                                echo '<span class="text-blue-400">' . htmlspecialchars($line) . '</span>';
                            } else {
                                echo htmlspecialchars($line);
                            }
                        }
                        echo '</pre>';
                    } else {
                        echo '<p class="text-gray-600">ログファイルが見つかりません</p>';
                    }
                    ?>
                </div>
                
                <!-- 推奨アクション -->
                <div class="bg-indigo-50 p-4 rounded">
                    <h2 class="font-semibold mb-3">🎯 次のステップ</h2>
                    <ol class="list-decimal list-inside space-y-2 text-sm">
                        <li>エラーがなければ、<a href="/" class="text-indigo-600 hover:underline">Insight-Boxを開く</a></li>
                        <li>エラーがあれば、上記のログを確認</li>
                        <li>OCR処理のログを確認（青い "INFO" 行を探す）</li>
                        <li>このファイル（debug.php）を削除</li>
                        <li>setup.phpも削除されているか確認</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

