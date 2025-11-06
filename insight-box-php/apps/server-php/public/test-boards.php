<?php

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

try {
    // ボードリストのテスト
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "<pre>";
    echo "Testing Board Functionality\n";
    echo "==========================\n\n";
    
    // ユーザーを取得
    $user = \App\Models\User::first();
    if (!$user) {
        echo "Error: No users found in database\n";
        exit;
    }
    
    echo "User found: {$user->name} (ID: {$user->id})\n\n";
    
    // BoardRepositoryをテスト
    $boardRepo = new \App\Repositories\BoardRepository();
    
    echo "Getting boards for user {$user->id}...\n";
    $boards = $boardRepo->getUserBoards($user->id);
    
    echo "Found " . count($boards) . " boards\n\n";
    
    if (count($boards) > 0) {
        echo "Boards:\n";
        print_r($boards);
    }
    
    echo "\n✅ Test completed successfully!\n";
    echo "</pre>";
    
} catch (\Exception $e) {
    echo "<pre>";
    echo "❌ Error occurred:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

