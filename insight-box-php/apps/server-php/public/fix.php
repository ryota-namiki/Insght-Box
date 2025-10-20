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

// ステップ1: データベースファイルとストレージディレクトリの権限修正
echo '<div class="p-4 border-2 border-blue-500 rounded">';
echo '<h2 class="font-semibold text-blue-900 mb-2">📁 ステップ1: ファイルシステム権限</h2>';

// database.sqlite
$dbPath = $basePath . '/database/database.sqlite';
if (file_exists($dbPath)) {
  $currentPerms = substr(sprintf('%o', fileperms($dbPath)), -4);
  echo '<p class="text-sm text-gray-600">database.sqlite: ' . $currentPerms . '</p>';

  if (@chmod($dbPath, 0666)) {
    echo '<p class="text-sm text-green-600">✅ database.sqlite を 0666 に変更</p>';
  }
}

// database/ ディレクトリ
$dbDir = $basePath . '/database';
if (@chmod($dbDir, 0777)) {
  echo '<p class="text-sm text-green-600">✅ database/ を 0777 に変更</p>';
}

// storage/app/data/ ディレクトリ（JSON保存用）
$dataDir = $basePath . '/storage/app/data';
if (!file_exists($dataDir)) {
  @mkdir($dataDir, 0777, true);
  echo '<p class="text-sm text-green-600">✅ storage/app/data/ を作成しました</p>';
} else {
  @chmod($dataDir, 0777);
  echo '<p class="text-sm text-green-600">✅ storage/app/data/ のパーミッションを設定</p>';
}

// storage/app/private/ ディレクトリ（アップロードファイル用）
$privateDir = $basePath . '/storage/app/private';
if (!file_exists($privateDir)) {
  @mkdir($privateDir, 0777, true);
  echo '<p class="text-sm text-green-600">✅ storage/app/private/ を作成しました</p>';
} else {
  @chmod($privateDir, 0777);
  echo '<p class="text-sm text-green-600">✅ storage/app/private/ のパーミッションを設定</p>';
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

// ステップ4: データベースファイル削除と再作成
echo '<div class="p-4 border-2 border-indigo-500 rounded">';
echo '<h2 class="font-semibold text-indigo-900 mb-2">🗄️ ステップ4: データベース完全再構築（v2）</h2>';

$dbPath = $basePath . '/database/database.sqlite';

try {
  // 既存のデータベースファイルを削除
  if (file_exists($dbPath)) {
    if (@unlink($dbPath)) {
      echo '<p class="text-sm text-green-600">✅ 既存のデータベースファイルを削除しました</p>';
    } else {
      echo '<p class="text-sm text-yellow-600">⚠️ データベースファイルの削除に失敗（権限を確認してください）</p>';
    }
  } else {
    echo '<p class="text-sm text-blue-600">ℹ️ データベースファイルは存在しませんでした</p>';
  }

  // 新しい空のデータベースファイルを作成
  if (@touch($dbPath)) {
    @chmod($dbPath, 0666);
    echo '<p class="text-sm text-green-600">✅ 新しいデータベースファイルを作成しました (0666)</p>';
  } else {
    echo '<p class="text-sm text-red-600">❌ データベースファイルの作成に失敗</p>';
  }

  // マイグレーション実行（エラーが出ても続行）
  echo '<p class="text-sm text-blue-600">🔄 マイグレーション実行中...</p>';
  @exec('cd ' . escapeshellarg($basePath) . ' && php artisan migrate --force 2>&1', $output4, $ret4);

  if ($ret4 === 0) {
    echo '<p class="text-sm text-green-600">✅ マイグレーション成功</p>';
    if (!empty($output4)) {
      $outputText = implode("\n", $output4);
      // "Nothing to migrate" が含まれているかチェック
      if (strpos($outputText, 'Nothing to migrate') !== false) {
        echo '<p class="text-sm text-yellow-600">⚠️ 「Nothing to migrate」- マイグレーションファイルが見つかりません</p>';
      }
      echo '<pre class="text-xs text-gray-600 bg-gray-50 p-2 rounded mt-2 overflow-auto max-h-40">' . htmlspecialchars($outputText) . '</pre>';
    }
  } else {
    echo '<p class="text-sm text-yellow-600">⚠️ マイグレーション一部失敗（続行します）</p>';
    if (!empty($output4)) {
      $outputText = implode("\n", $output4);
      // eventsテーブルのエラーの場合は警告レベル
      if (strpos($outputText, 'no such table: events') !== false) {
        echo '<p class="text-sm text-blue-600">ℹ️ eventsテーブルはステップ5で作成します</p>';
      }
      echo '<pre class="text-xs text-yellow-600 bg-yellow-50 p-2 rounded mt-2 overflow-auto max-h-40">' . htmlspecialchars($outputText) . '</pre>';
    }
  }

  // テーブル一覧を確認
  if (file_exists($dbPath)) {
    try {
      $pdo = new PDO('sqlite:' . $dbPath);
      $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

      if (!empty($tables)) {
        echo '<p class="text-sm text-green-600 mt-2">📋 作成されたテーブル: ' . implode(', ', $tables) . '</p>';
      } else {
        echo '<p class="text-sm text-red-600 mt-2">❌ テーブルが1つも作成されていません</p>';
      }
    } catch (Exception $e) {
      echo '<p class="text-sm text-yellow-600 mt-2">⚠️ テーブル確認失敗: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
  }

} catch (Exception $e) {
  echo '<p class="text-sm text-red-600">❌ データベース再構築エラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

// ステップ5: eventsテーブル作成とデフォルトイベント
echo '<div class="p-4 border-2 border-green-500 rounded">';
echo '<h2 class="font-semibold text-green-900 mb-2">📋 ステップ5: eventsテーブルとデフォルトイベント作成</h2>';

try {
  // PDOで直接テーブルを作成
  if (file_exists($dbPath)) {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // eventsテーブルの存在確認
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='events'")->fetchColumn();

    if (!$tableExists) {
      echo '<p class="text-sm text-blue-600">🔧 eventsテーブルを作成中...</p>';

      // eventsテーブルを作成（locationカラムも含む）
      $pdo->exec("
                CREATE TABLE events (
                    id TEXT PRIMARY KEY NOT NULL,
                    name TEXT NOT NULL,
                    description TEXT,
                    location TEXT,
                    start_date TEXT NOT NULL,
                    end_date TEXT NOT NULL,
                    created_at TEXT,
                    updated_at TEXT
                )
            ");

      echo '<p class="text-sm text-green-600">✅ eventsテーブルを作成しました</p>';
    } else {
      echo '<p class="text-sm text-green-600">✅ eventsテーブルは既に存在します</p>';

      // locationカラムが存在するか確認
      $columns = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
      $hasLocation = false;
      foreach ($columns as $col) {
        if ($col['name'] === 'location') {
          $hasLocation = true;
          break;
        }
      }

      if (!$hasLocation) {
        echo '<p class="text-sm text-blue-600">🔧 locationカラムを追加中...</p>';
        $pdo->exec("ALTER TABLE events ADD COLUMN location TEXT");
        echo '<p class="text-sm text-green-600">✅ locationカラムを追加しました</p>';
      }
    }

    // デフォルトイベントの確認と作成
    $eventExists = $pdo->query("SELECT COUNT(*) FROM events WHERE id = 'default-event'")->fetchColumn();

    if (!$eventExists) {
      $now = date('Y-m-d H:i:s');
      $endDate = date('Y-m-d H:i:s', strtotime('+1 year'));

      $stmt = $pdo->prepare("
                INSERT INTO events (id, name, description, location, start_date, end_date, created_at, updated_at)
                VALUES (:id, :name, :description, :location, :start_date, :end_date, :created_at, :updated_at)
            ");

      $stmt->execute([
        ':id' => 'default-event',
        ':name' => 'デフォルトイベント',
        ':description' => 'システム作成',
        ':location' => null,
        ':start_date' => $now,
        ':end_date' => $endDate,
        ':created_at' => $now,
        ':updated_at' => $now,
      ]);

      echo '<p class="text-sm text-green-600">✅ デフォルトイベントを作成しました</p>';
    } else {
      echo '<p class="text-sm text-blue-600">ℹ️ デフォルトイベントは既に存在します</p>';
    }

    // イベント数を確認
    $eventCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    echo '<p class="text-sm text-gray-600 mt-2">📊 イベント数: ' . $eventCount . '件</p>';

  } else {
    echo '<p class="text-sm text-red-600">❌ データベースファイルが見つかりません</p>';
  }
} catch (Exception $e) {
  echo '<p class="text-sm text-red-600">❌ エラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
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
