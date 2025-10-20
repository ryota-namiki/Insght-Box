<?php

/**
 * エラー詳細表示スクリプト
 * デバッグモードを強制的にONにしてエラーを表示
 */

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>エラー詳細</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">🔍 エラー詳細表示</h1>
        <div class="space-y-4">';

try {
  // .envを一時的にデバッグモードに変更
  putenv('APP_ENV=local');
  putenv('APP_DEBUG=true');

  require __DIR__ . '/../vendor/autoload.php';

  echo '<p class="text-green-700">✅ autoload成功</p>';

  $app = require_once __DIR__ . '/../bootstrap/app.php';

  echo '<p class="text-green-700">✅ app起動成功</p>';

  // リクエストをシミュレート
  $request = Illuminate\Http\Request::create('/cards', 'GET');

  echo '<p class="text-blue-700">🔄 /cards にアクセスを試みます...</p>';

  // レスポンスを取得
  $response = $app->handle($request);

  echo '<p class="text-green-700">✅ レスポンス取得成功</p>';
  echo '<p class="text-gray-700">ステータスコード: ' . $response->getStatusCode() . '</p>';

  if ($response->getStatusCode() === 200) {
    echo '<div class="mt-6 p-4 bg-green-50 border border-green-200 rounded">
                <p class="text-green-900 font-semibold">🎉 正常に動作しています！</p>
                <p class="text-green-800 text-sm mt-2">エラーは別の原因かもしれません。</p>
                <a href="/cards" class="mt-4 inline-block px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    カード一覧を開く
                </a>
            </div>';
  } else {
    echo '<div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-yellow-900">ステータス: ' . $response->getStatusCode() . '</p>
                <pre class="mt-2 text-xs bg-gray-900 text-yellow-400 p-3 rounded overflow-x-auto">' . htmlspecialchars($response->getContent()) . '</pre>
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
} catch (Throwable $e) {
  echo '<div class="mt-6 p-4 bg-red-50 border border-red-200 rounded">
            <h2 class="text-red-900 font-semibold mb-2">❌ 致命的エラー</h2>
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
