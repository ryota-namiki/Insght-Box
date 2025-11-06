<?php

/**
 * ユーザー作成/パスワードリセットスクリプト
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $basePath = realpath(__DIR__ . '/..');
        require $basePath . '/vendor/autoload.php';
        
        $app = require_once $basePath . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('全ての項目を入力してください');
        }
        
        // メールアドレスで既存ユーザーを検索
        $user = \App\Models\User::where('email', $email)->first();
        
        if ($user) {
            // 既存ユーザーのパスワード更新
            $user->name = $name;
            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            $user->save();
            
            $message = "✅ ユーザー「{$name}」のパスワードを更新しました！";
        } else {
            // 新規ユーザー作成
            $user = \App\Models\User::create([
                'name' => $name,
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ]);
            
            $message = "✅ ユーザー「{$name}」を作成しました！";
        }
        
    } catch (Exception $e) {
        $error = "❌ エラー: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー作成/パスワードリセット</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">👤 ユーザー管理</h1>
        
        <?php if ($message): ?>
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
            <p class="text-green-900"><?= htmlspecialchars($message) ?></p>
            <a href="/login" class="mt-3 inline-block px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                ログインページへ
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
            <p class="text-red-900"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    名前
                </label>
                <input 
                    type="text" 
                    name="name" 
                    value="並木亮太"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    メールアドレス
                </label>
                <input 
                    type="email" 
                    name="email" 
                    value="namiki.ryota@njc.co.jp"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    パスワード
                </label>
                <input 
                    type="text" 
                    name="password" 
                    value="password1234"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    required
                >
                <p class="mt-1 text-xs text-gray-500">最低8文字</p>
            </div>
            
            <button 
                type="submit" 
                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium"
            >
                作成/更新
            </button>
        </form>
        
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
            <p class="text-yellow-900 text-sm">
                <strong>💡 ヒント:</strong><br>
                既存のメールアドレスを入力すると、パスワードが更新されます。<br>
                新しいメールアドレスを入力すると、新規ユーザーが作成されます。
            </p>
        </div>
        
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded">
            <p class="text-red-900 text-sm font-semibold">⚠️ セキュリティ警告</p>
            <p class="text-red-800 text-sm mt-1">完了後、このファイル（create-user.php）を必ず削除してください。</p>
        </div>
    </div>
</body>
</html>

