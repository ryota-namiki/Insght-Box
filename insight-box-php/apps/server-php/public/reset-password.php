<?php
/**
 * パスワードリセットスクリプト
 * サーバー側で https://your-domain.com/reset-password.php にアクセスして実行
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>パスワードリセット</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">🔐 パスワードリセット</h1>
    
    <?php
    try {
      // ユーザー一覧を取得
      $users = User::all();
      
      if ($users->isEmpty()) {
        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
        echo '⚠️ ユーザーが見つかりません。先に登録してください。';
        echo '</div>';
      } else {
        echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
        echo '<p class="font-semibold mb-2">📊 登録ユーザー一覧</p>';
        echo '<ul class="list-disc list-inside">';
        foreach ($users as $user) {
          echo '<li>ID: ' . $user->id . ' | Name: ' . htmlspecialchars($user->name) . ' | Email: ' . htmlspecialchars($user->email) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        // POSTリクエストの場合、パスワードをリセット
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['new_password'])) {
          $userId = intval($_POST['user_id']);
          $newPassword = $_POST['new_password'];
          
          $user = User::find($userId);
          
          if ($user) {
            // パスワードをハッシュ化して保存
            $user->password = Hash::make($newPassword);
            $user->save();
            
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
            echo '✅ パスワードをリセットしました！';
            echo '<p class="mt-2"><strong>ユーザー:</strong> ' . htmlspecialchars($user->email) . '</p>';
            echo '<p><strong>新しいパスワード:</strong> ' . htmlspecialchars($newPassword) . '</p>';
            echo '</div>';
            
            // パスワード検証
            $isValid = Hash::check($newPassword, $user->password);
            echo '<div class="bg-' . ($isValid ? 'green' : 'red') . '-100 border border-' . ($isValid ? 'green' : 'red') . '-400 text-' . ($isValid ? 'green' : 'red') . '-700 px-4 py-3 rounded mb-4">';
            echo $isValid ? '✅ パスワード検証: 成功' : '❌ パスワード検証: 失敗';
            echo '</div>';
          } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
            echo '❌ ユーザーが見つかりません';
            echo '</div>';
          }
        }
        
        // パスワードリセットフォーム
        echo '<form method="POST" class="space-y-4">';
        echo '<div>';
        echo '<label class="block text-sm font-medium text-gray-700 mb-2">ユーザーを選択</label>';
        echo '<select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-md" required>';
        foreach ($users as $user) {
          echo '<option value="' . $user->id . '">' . htmlspecialchars($user->name) . ' (' . htmlspecialchars($user->email) . ')</option>';
        }
        echo '</select>';
        echo '</div>';
        
        echo '<div>';
        echo '<label class="block text-sm font-medium text-gray-700 mb-2">新しいパスワード</label>';
        echo '<input type="text" name="new_password" value="password123" class="w-full px-4 py-2 border border-gray-300 rounded-md" required>';
        echo '</div>';
        
        echo '<button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">';
        echo 'パスワードをリセット';
        echo '</button>';
        echo '</form>';
      }
      
      // 現在のパスワードハッシュを表示
      echo '<div class="mt-6 bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">';
      echo '<p class="font-semibold mb-2">🔍 現在のパスワードハッシュ</p>';
      foreach ($users as $user) {
        echo '<div class="mb-2 text-xs">';
        echo '<strong>' . htmlspecialchars($user->email) . ':</strong><br>';
        echo '<code class="bg-white px-2 py-1 rounded">' . substr($user->password, 0, 50) . '...</code>';
        echo '</div>';
      }
      echo '</div>';
      
    } catch (Exception $e) {
      echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
      echo '❌ エラー: ' . htmlspecialchars($e->getMessage());
      echo '<pre class="mt-2 text-xs overflow-auto">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
      echo '</div>';
    }
    ?>
    
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
      <p class="text-sm text-yellow-800">
        <strong>⚠️ セキュリティ警告:</strong><br>
        このファイルは使用後すぐに削除してください！<br>
        <code class="bg-yellow-100 px-2 py-1 rounded">public/reset-password.php</code>
      </p>
    </div>
    
    <div class="mt-4 text-center">
      <a href="/" class="text-indigo-600 hover:text-indigo-800">← トップページへ戻る</a>
    </div>
  </div>
</body>
</html>

