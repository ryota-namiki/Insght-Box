<?php
/**
 * セットアップスクリプト
 * 
 * このファイルをブラウザで開いて、初期セットアップを実行できます
 * SSH不要でFileZillaだけでデプロイ完了！
 * 
 * アクセス: https://yourdomain.com/setup.php
 * 
 * セットアップ完了後は、このファイルを削除してください
 */

// セットアップ完了フラグ
$setupCompleteFile = __DIR__ . '/../storage/app/.setup_complete';

if (file_exists($setupCompleteFile)) {
    die('⚠️ セットアップは既に完了しています。このファイルを削除してください。');
}

$errors = [];
$warnings = [];
$success = [];
$step = $_POST['step'] ?? 'check';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insight-Box セットアップ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📦 Insight-Box セットアップ</h1>
            <p class="text-gray-600 mb-8">SSH不要で簡単セットアップ！</p>

            <?php if ($step === 'check'): ?>
                <!-- ステップ1: 環境チェック -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">🔍 環境チェック</h2>
                    
                    <?php
                    // PHP バージョンチェック
                    if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
                        $success[] = 'PHP ' . PHP_VERSION . ' ✅';
                    } else {
                        $errors[] = 'PHP 8.1以上が必要です（現在: ' . PHP_VERSION . '）';
                    }
                    
                    // Composer autoloadチェック
                    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                        $success[] = 'Composer依存関係がインストール済み ✅';
                        require __DIR__ . '/../vendor/autoload.php';
                    } else {
                        $errors[] = 'vendor/ ディレクトリがありません。ローカルで composer install を実行して vendor/ もアップロードしてください。';
                    }
                    
                    // .env ファイルチェック
                    if (file_exists(__DIR__ . '/../.env')) {
                        $success[] = '.env ファイルが存在します ✅';
                    } else {
                        $warnings[] = '.env ファイルがありません（これから作成します）';
                    }
                    
                    // 書き込み権限チェック（自動修正試行）
                    $storagePath = __DIR__ . '/../storage';
                    $bootstrapCachePath = __DIR__ . '/../bootstrap/cache';
                    
                    // storage/ の権限チェックと修正
                    if (!is_writable($storagePath)) {
                        // パーミッション修正を試みる
                        @chmod($storagePath, 0775);
                        @chmod($storagePath . '/app', 0775);
                        @chmod($storagePath . '/framework', 0775);
                        @chmod($storagePath . '/logs', 0775);
                        
                        // 再チェック
                        if (is_writable($storagePath)) {
                            $success[] = 'storage/ ディレクトリのパーミッションを自動修正しました ✅';
                        } else {
                            $warnings[] = 'storage/ ディレクトリに書き込み権限がありません（手動で設定が必要）';
                        }
                    } else {
                        $success[] = 'storage/ ディレクトリに書き込み権限あり ✅';
                    }
                    
                    // bootstrap/cache/ の権限チェックと修正
                    if (!is_writable($bootstrapCachePath)) {
                        @chmod($bootstrapCachePath, 0775);
                        
                        if (is_writable($bootstrapCachePath)) {
                            $success[] = 'bootstrap/cache/ ディレクトリのパーミッションを自動修正しました ✅';
                        } else {
                            $warnings[] = 'bootstrap/cache/ ディレクトリに書き込み権限がありません（手動で設定が必要）';
                        }
                    } else {
                        $success[] = 'bootstrap/cache/ ディレクトリに書き込み権限あり ✅';
                    }
                    ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
                            <?php foreach ($success as $msg): ?>
                                <p class="text-green-800">✅ <?= htmlspecialchars($msg) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($warnings)): ?>
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                            <?php foreach ($warnings as $msg): ?>
                                <p class="text-yellow-800">⚠️ <?= htmlspecialchars($msg) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
                            <?php foreach ($errors as $msg): ?>
                                <p class="text-red-800">❌ <?= htmlspecialchars($msg) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($errors)): ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="configure">
                        
                        <?php if (empty($warnings)): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6">
                                <p class="text-blue-800">
                                    <span class="material-icons text-sm align-middle">info</span>
                                    環境チェックをパスしました！次のステップに進んでください。
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
                                <p class="text-yellow-800">
                                    <span class="material-icons text-sm align-middle">warning</span>
                                    警告がありますが、続行できます。セットアップ中に自動修正を試みます。
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                            次へ：環境設定 →
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-red-50 border border-red-200 rounded p-4">
                        <p class="text-red-800 font-medium">エラーを修正してから、ページを再読み込みしてください。</p>
                        <p class="text-red-700 text-sm mt-2">
                            vendor/ ディレクトリがない場合は、ローカルで <code>composer install</code> を実行してから、vendor/ も含めてアップロードしてください。
                        </p>
                    </div>
                <?php endif; ?>

            <?php elseif ($step === 'configure'): ?>
                <!-- ステップ2: 環境設定 -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">⚙️ 環境設定</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="install">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">アプリケーションURL</label>
                            <input type="url" name="app_url" required class="w-full px-4 py-2 border border-gray-300 rounded-md" 
                                   value="https://<?= $_SERVER['HTTP_HOST'] ?>" placeholder="https://yourdomain.com">
                            <p class="text-xs text-gray-500 mt-1">サイトのURLを入力してください</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">データベース接続</label>
                            <select name="db_connection" class="w-full px-4 py-2 border border-gray-300 rounded-md" required>
                                <option value="sqlite">SQLite（簡単・推奨）</option>
                                <option value="mysql">MySQL</option>
                                <option value="pgsql">PostgreSQL</option>
                            </select>
                        </div>
                        
                        <div id="mysql-config" style="display:none;" class="space-y-4 pl-4 border-l-4 border-blue-200">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">データベース名</label>
                                <input type="text" name="db_database" class="w-full px-4 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ユーザー名</label>
                                <input type="text" name="db_username" class="w-full px-4 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">パスワード</label>
                                <input type="password" name="db_password" class="w-full px-4 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ホスト</label>
                                <input type="text" name="db_host" class="w-full px-4 py-2 border border-gray-300 rounded-md" value="127.0.0.1">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">OpenAI API Key（Webクリップ要約用）</label>
                            <input type="text" name="openai_key" class="w-full px-4 py-2 border border-gray-300 rounded-md font-mono text-sm" 
                                   placeholder="sk-proj-...">
                            <p class="text-xs text-gray-500 mt-1">任意。設定しない場合、Webクリップ要約機能が使えません。</p>
                        </div>
                        
                        <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                            セットアップ実行 →
                        </button>
                    </form>
                </div>
                
                <script>
                document.querySelector('select[name="db_connection"]').addEventListener('change', function(e) {
                    document.getElementById('mysql-config').style.display = 
                        (e.target.value === 'mysql' || e.target.value === 'pgsql') ? 'block' : 'none';
                });
                </script>

            <?php elseif ($step === 'install'): ?>
                <!-- ステップ3: セットアップ実行 -->
                <?php
                require __DIR__ . '/../vendor/autoload.php';
                
                try {
                    $appUrl = $_POST['app_url'] ?? 'http://localhost';
                    $dbConnection = $_POST['db_connection'] ?? 'sqlite';
                    $openaiKey = $_POST['openai_key'] ?? '';
                    
                    // パーミッション修正を試みる
                    $dirs = [
                        __DIR__ . '/../storage',
                        __DIR__ . '/../storage/app',
                        __DIR__ . '/../storage/app/public',
                        __DIR__ . '/../storage/framework',
                        __DIR__ . '/../storage/framework/cache',
                        __DIR__ . '/../storage/framework/sessions',
                        __DIR__ . '/../storage/framework/views',
                        __DIR__ . '/../storage/logs',
                        __DIR__ . '/../bootstrap/cache',
                    ];
                    
                    foreach ($dirs as $dir) {
                        if (file_exists($dir)) {
                            @chmod($dir, 0775);
                        } else {
                            @mkdir($dir, 0775, true);
                        }
                    }
                    $success[] = 'ディレクトリのパーミッションを設定しました';
                    
                    // .env ファイルを作成
                    $envContent = file_get_contents(__DIR__ . '/../.env.example');
                    
                    // APP_URL を設定
                    $envContent = preg_replace('/^APP_URL=.*/m', "APP_URL={$appUrl}", $envContent);
                    $envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $envContent);
                    $envContent = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $envContent);
                    
                    // アプリケーションキーを生成
                    $appKey = 'base64:' . base64_encode(random_bytes(32));
                    $envContent = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$appKey}", $envContent);
                    
                    // データベース設定
                    if ($dbConnection === 'sqlite') {
                        $dbDir = __DIR__ . '/../database';
                        
                        // データベースディレクトリが存在することを確認
                        if (!is_dir($dbDir)) {
                            mkdir($dbDir, 0775, true);
                        }
                        
                        $dbPath = realpath($dbDir) . '/database.sqlite';
                        
                        // データベースファイルを作成
                        if (!file_exists($dbPath)) {
                            file_put_contents($dbPath, '');
                            @chmod($dbPath, 0664);
                        }
                        
                        // パーミッション確認
                        if (!is_writable($dbPath)) {
                            @chmod($dbPath, 0664);
                        }
                        
                        $envContent = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=sqlite', $envContent);
                        $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$dbPath}", $envContent);
                        $success[] = "SQLiteデータベースを作成しました: {$dbPath}";
                    } else {
                        $dbDatabase = $_POST['db_database'] ?? '';
                        $dbUsername = $_POST['db_username'] ?? '';
                        $dbPassword = $_POST['db_password'] ?? '';
                        $dbHost = $_POST['db_host'] ?? '127.0.0.1';
                        
                        $envContent = preg_replace('/^DB_CONNECTION=.*/m', "DB_CONNECTION={$dbConnection}", $envContent);
                        $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$dbDatabase}", $envContent);
                        $envContent = preg_replace('/^DB_USERNAME=.*/m', "DB_USERNAME={$dbUsername}", $envContent);
                        $envContent = preg_replace('/^DB_PASSWORD=.*/m', "DB_PASSWORD={$dbPassword}", $envContent);
                        $envContent = preg_replace('/^DB_HOST=.*/m', "DB_HOST={$dbHost}", $envContent);
                    }
                    
                    // OpenAI API Key
                    if ($openaiKey) {
                        if (strpos($envContent, 'OPENAI_API_KEY=') !== false) {
                            $envContent = preg_replace('/^OPENAI_API_KEY=.*/m', "OPENAI_API_KEY={$openaiKey}", $envContent);
                        } else {
                            $envContent .= "\nOPENAI_API_KEY={$openaiKey}\n";
                        }
                    }
                    
                    // .env ファイルを保存
                    file_put_contents(__DIR__ . '/../.env', $envContent);
                    $success[] = '.env ファイルを作成しました';
                    
                    // SQLiteの場合、データベースファイルが確実に存在することを確認
                    if ($dbConnection === 'sqlite') {
                        $dbDir = __DIR__ . '/../database';
                        
                        // databaseディレクトリ確認
                        if (!is_dir($dbDir)) {
                            mkdir($dbDir, 0775, true);
                        }
                        
                        $dbPath = realpath($dbDir) . '/database.sqlite';
                        
                        // データベースファイル作成
                        if (!file_exists($dbPath)) {
                            file_put_contents($dbPath, '');
                            @chmod($dbPath, 0664);
                        }
                        
                        // 最終確認
                        if (!file_exists($dbPath)) {
                            throw new Exception("データベースファイルの作成に失敗しました。database/ ディレクトリの書き込み権限を確認してください。パス: {$dbPath}");
                        }
                        
                        if (!is_writable($dbPath)) {
                            @chmod($dbPath, 0664);
                        }
                    }
                    
                    // 既存のキャッシュをクリア（重要！）
                    $cacheDirs = [
                        __DIR__ . '/../bootstrap/cache/config.php',
                        __DIR__ . '/../bootstrap/cache/routes-v7.php',
                        __DIR__ . '/../bootstrap/cache/services.php',
                        __DIR__ . '/../bootstrap/cache/packages.php',
                    ];
                    foreach ($cacheDirs as $cacheFile) {
                        if (file_exists($cacheFile)) {
                            @unlink($cacheFile);
                        }
                    }
                    
                    // Artisan コマンドを実行
                    $app = require_once __DIR__ . '/../bootstrap/app.php';
                    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                    
                    // 設定をリロード
                    $kernel->call('config:clear');
                    
                    // マイグレーション実行
                    $kernel->call('migrate', ['--force' => true]);
                    $success[] = 'データベースマイグレーションを実行しました';
                    
                    // キャッシュ最適化
                    $kernel->call('config:cache');
                    $kernel->call('route:cache');
                    $kernel->call('view:cache');
                    $success[] = 'キャッシュを最適化しました';
                    
                    // セットアップ完了フラグを作成
                    file_put_contents($setupCompleteFile, date('Y-m-d H:i:s'));
                    
                    $setupComplete = true;
                    
                } catch (Exception $e) {
                    $errors[] = 'エラーが発生しました: ' . $e->getMessage();
                    $setupComplete = false;
                }
                ?>
                
                <?php if (!empty($success)): ?>
                    <div class="mb-6 p-6 bg-green-50 border border-green-200 rounded-lg">
                        <h3 class="font-semibold text-green-900 mb-3 flex items-center">
                            <span class="material-icons mr-2">check_circle</span>
                            セットアップ成功！
                        </h3>
                        <?php foreach ($success as $msg): ?>
                            <p class="text-green-800 mb-1">✅ <?= htmlspecialchars($msg) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-6 bg-red-50 border border-red-200 rounded-lg">
                        <h3 class="font-semibold text-red-900 mb-3">エラー</h3>
                        <?php foreach ($errors as $msg): ?>
                            <p class="text-red-800 mb-1">❌ <?= htmlspecialchars($msg) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($setupComplete ?? false): ?>
                    <div class="mt-8 p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg">
                        <h3 class="text-xl font-bold text-indigo-900 mb-4">🎉 セットアップ完了！</h3>
                        
                        <div class="space-y-3 mb-6">
                            <p class="text-gray-700">✅ アプリケーションが正常にセットアップされました</p>
                            <p class="text-gray-700">✅ データベーステーブルが作成されました</p>
                            <p class="text-gray-700">✅ キャッシュが最適化されました</p>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-300 rounded p-4 mb-6">
                            <p class="text-yellow-900 font-medium mb-2">⚠️ 重要：セキュリティ対策</p>
                            <p class="text-yellow-800 text-sm">
                                このファイル（setup.php）を<strong>必ず削除</strong>してください！<br>
                                FileZillaで接続して、public/setup.php を削除してください。
                            </p>
                        </div>
                        
                        <div class="flex gap-3">
                            <a href="/" class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-center font-medium">
                                Insight-Boxを開く →
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <button onclick="history.back()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        ← 戻る
                    </button>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Insight-Box Setup Wizard v1.0</p>
        </div>
    </div>
</body>
</html>

