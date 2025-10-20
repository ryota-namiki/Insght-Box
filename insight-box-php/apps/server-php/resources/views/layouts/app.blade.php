<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Insight-Box')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Alpine.js (軽量なインタラクティブ機能用) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }
        
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        
        .toast {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
    
    @yield('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- ナビゲーションバー -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="{{ route('cards.index') }}" class="text-2xl font-bold text-indigo-600">
                        📦 Insight-Box
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="{{ route('cards.index') }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('cards.*') ? 'bg-gray-100 text-indigo-600' : 'text-gray-700' }}">
                        <span class="material-icons text-sm mr-1">list</span>
                        カード
                    </a>
                    <a href="{{ route('board.index') }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('board.*') ? 'bg-gray-100 text-indigo-600' : 'text-gray-700' }}">
                        <span class="material-icons text-sm mr-1">dashboard</span>
                        ボード
                    </a>
                    <a href="{{ route('events.index') }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 {{ request()->routeIs('events.*') ? 'bg-gray-100 text-indigo-600' : 'text-gray-700' }}">
                        <span class="material-icons text-sm mr-1">event</span>
                        イベント
                    </a>
                    <a href="{{ route('cards.create') }}" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                        <span class="material-icons text-sm mr-1">add</span>
                        カード作成
                    </a>
                    
                    @auth
                    <!-- ユーザーメニュー -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                            <span class="material-icons text-sm mr-1">account_circle</span>
                            {{ auth()->user()->name }}
                            <span class="material-icons text-sm ml-1">arrow_drop_down</span>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-cloak
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-200">
                            <a href="{{ route('profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <span class="material-icons text-sm mr-2">person</span>
                                プロフィール
                            </a>
                            <hr class="my-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <span class="material-icons text-sm mr-2">logout</span>
                                    ログアウト
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <!-- 未認証ユーザー -->
                    <a href="{{ route('login') }}" class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                        ログイン
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                        登録
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
    
    <!-- メインコンテンツ -->
    <main class="mx-auto py-8" style="max-width: 75rem;">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded toast">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded toast">
                {{ session('error') }}
            </div>
        @endif
        
        @yield('content')
    </main>
    
    <!-- フッター -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                © 2025 Insight-Box. All rights reserved.
            </p>
        </div>
    </footer>
    
    @yield('scripts')
</body>
</html>

