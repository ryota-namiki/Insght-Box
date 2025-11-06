<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Insight-Box')</title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  
  <!-- Google Fonts: Roboto + Noto Sans JP -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600&family=Noto+Sans+JP:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS with Custom Config -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              DEFAULT: '#3A7BD5',
              light: '#6DD5FA',
              dark: '#1E40AF'
            },
            background: '#F9FAFB',
            surface: '#FFFFFF',
            text: {
              main: '#111827',
              subtle: '#6B7280'
            },
            divider: '#E5E7EB',
            success: '#16A34A',
            warning: '#FACC15',
            error: '#DC2626',
            accent: '#38BDF8'
          },
          fontFamily: {
            sans: ['Roboto', 'Noto Sans JP', 'sans-serif']
          },
          borderRadius: {
            'card': '12px',
            'btn': '8px'
          },
          boxShadow: {
            'card': '0 4px 8px rgba(0, 0, 0, 0.06)',
            'card-hover': '0 8px 16px rgba(0, 0, 0, 0.12)'
          },
          transitionDuration: {
            'fast': '150ms'
          }
        }
      }
    }
  </script>
  
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  
  <style>
    [x-cloak] { display: none !important; }
    
    body {
      font-family: 'Roboto', 'Noto Sans JP', sans-serif;
      background-color: #F9FAFB;
    }
    
    /* Card Hover Animation */
    .card {
      transition: all 0.15s ease-out;
    }
    
    .card:hover {
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
      border-color: #3A7BD5;
    }
    
    /* Button Hover */
    .btn-primary:hover {
      filter: brightness(0.95);
    }
    
    .btn-primary:active {
      transform: scale(0.98);
    }
    
    /* Toast Animation */
    .toast {
      animation: slideIn 0.2s ease-out;
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
    
    /* Nav Active State */
    .nav-active {
      background-color: #EFF6FF;
      color: #3A7BD5;
      font-weight: 600;
    }
  </style>
  
  @yield('styles')
</head>
<body class="bg-background min-h-screen flex flex-col">
  <!-- ナビゲーションバー -->
  <nav class="bg-surface shadow-sm flex-shrink-0" x-data="{ mobileMenuOpen: false }">
    <div class="w-full mx-auto px-4 md:px-10" style="max-width: 90rem;">
      <div class="flex justify-between items-center h-16">
        <!-- ロゴ -->
        <div class="flex items-center gap-2">
          <a href="{{ route('cards.index') }}" class="text-xl md:text-2xl font-semibold text-primary">
            📦 Insight-Box
          </a>
        </div>
        
        <!-- デスクトップメニュー -->
        <div class="hidden md:flex items-center gap-1">
          <a href="{{ route('cards.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('cards.*') && !request()->routeIs('cards.create') ? 'nav-active' : 'text-text-main' }}">
            <i data-lucide="list" class="w-4 h-4"></i>
            カード
          </a>
          <a href="{{ route('board.list') }}" class="flex items-center gap-2 px-3 py-2 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('board.*') ? 'nav-active' : 'text-text-main' }}">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            ボード
          </a>
          <a href="{{ route('events.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('events.*') ? 'nav-active' : 'text-text-main' }}">
            <i data-lucide="calendar" class="w-4 h-4"></i>
            イベント
          </a>
          <a href="{{ route('favorites.index') }}" class="flex items-center gap-2 px-3 py-2 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('favorites.*') ? 'nav-active' : 'text-text-main' }}">
            <i data-lucide="heart" class="w-4 h-4"></i>
            お気に入り
          </a>
          <a href="{{ route('cards.create') }}" class="flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary ml-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            カード作成
          </a>
          
          @auth
          <!-- ユーザーメニュー -->
          <div x-data="{ open: false }" class="relative ml-2">
            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 rounded-btn text-sm font-medium text-text-main hover:bg-gray-100 transition-fast">
              <i data-lucide="user" class="w-4 h-4"></i>
              {{ auth()->user()->name }}
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            
            <div x-show="open" @click.away="open = false" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute right-0 mt-2 w-48 bg-surface rounded-card shadow-card-hover py-1 z-10 border border-divider">
              <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-text-main hover:bg-gray-100">
                <i data-lucide="user" class="w-4 h-4"></i>
                プロフィール
              </a>
              <hr class="my-1 border-divider">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-text-main hover:bg-gray-100">
                  <i data-lucide="log-out" class="w-4 h-4"></i>
                  ログアウト
                </button>
              </form>
            </div>
          </div>
          @else
          <!-- 未認証ユーザー -->
          <a href="{{ route('login') }}" class="flex items-center gap-2 px-3 py-2 rounded-btn text-sm font-medium text-text-main hover:bg-gray-100 transition-fast">
            ログイン
          </a>
          <a href="{{ route('register') }}" class="flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary">
            登録
          </a>
          @endauth
        </div>
        
        <!-- モバイルメニュー（タブレット以下） -->
        <div class="flex md:hidden items-center gap-2">
          <!-- カード作成ボタン -->
          <a href="{{ route('cards.create') }}" class="flex items-center justify-center w-10 h-10 bg-primary text-white rounded-btn hover:brightness-95 transition-fast btn-primary">
            <i data-lucide="plus" class="w-5 h-5"></i>
          </a>
          
          @auth
          <!-- アカウントアイコン -->
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center justify-center w-10 h-10 rounded-btn text-text-main hover:bg-gray-100 transition-fast">
              <i data-lucide="user" class="w-5 h-5"></i>
            </button>
            
            <div x-show="open" @click.away="open = false" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute right-0 mt-2 w-48 bg-surface rounded-card shadow-card-hover py-1 z-10 border border-divider">
              <div class="px-4 py-2 text-sm font-medium text-text-main border-b border-divider">
                {{ auth()->user()->name }}
              </div>
              <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-text-main hover:bg-gray-100">
                <i data-lucide="user" class="w-4 h-4"></i>
                プロフィール
              </a>
              <hr class="my-1 border-divider">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-text-main hover:bg-gray-100">
                  <i data-lucide="log-out" class="w-4 h-4"></i>
                  ログアウト
                </button>
              </form>
            </div>
          </div>
          @else
          <a href="{{ route('login') }}" class="flex items-center justify-center w-10 h-10 rounded-btn text-text-main hover:bg-gray-100 transition-fast">
            <i data-lucide="log-in" class="w-5 h-5"></i>
          </a>
          @endauth
          
          <!-- ハンバーガーメニューボタン -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="flex items-center justify-center w-10 h-10 rounded-btn text-text-main hover:bg-gray-100 transition-fast">
            <i data-lucide="menu" x-show="!mobileMenuOpen" class="w-6 h-6"></i>
            <i data-lucide="x" x-show="mobileMenuOpen" class="w-6 h-6" x-cloak></i>
          </button>
        </div>
      </div>
      
      <!-- モバイルメニュー展開部分 -->
      <div x-show="mobileMenuOpen" 
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 -translate-y-2"
           x-transition:enter-end="opacity-100 translate-y-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 translate-y-0"
           x-transition:leave-end="opacity-0 -translate-y-2"
           @click.away="mobileMenuOpen = false"
           class="md:hidden py-4 border-t border-divider"
           x-cloak>
        <div class="flex flex-col gap-1">
          <a href="{{ route('cards.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('cards.*') && !request()->routeIs('cards.create') ? 'bg-blue-50 text-primary font-medium' : 'text-text-main' }}">
            <i data-lucide="list" class="w-5 h-5"></i>
            カード
          </a>
          <a href="{{ route('board.list') }}" class="flex items-center gap-3 px-4 py-3 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('board.*') ? 'bg-blue-50 text-primary font-medium' : 'text-text-main' }}">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            ボード
          </a>
          <a href="{{ route('events.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('events.*') ? 'bg-blue-50 text-primary font-medium' : 'text-text-main' }}">
            <i data-lucide="calendar" class="w-5 h-5"></i>
            イベント
          </a>
          <a href="{{ route('favorites.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-btn text-sm hover:bg-gray-100 transition-fast {{ request()->routeIs('favorites.*') ? 'bg-blue-50 text-primary font-medium' : 'text-text-main' }}">
            <i data-lucide="heart" class="w-5 h-5"></i>
            お気に入り
          </a>
        </div>
      </div>
    </div>
  </nav>
  
  <!-- トーストメッセージ（固定位置） -->
  @if(session('success'))
  <div x-data="{ show: true }" 
       x-show="show" 
       x-init="setTimeout(() => show = false, 3000)"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 translate-y-0"
       x-transition:leave-end="opacity-0 -translate-y-2"
       class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-green-50 border border-success text-success px-6 rounded-btn toast flex items-center gap-2 shadow-card-hover"
       style="height: 44px; width: fit-content;">
    <i data-lucide="check-circle" class="w-5 h-5"></i>
    {{ session('success') }}
  </div>
  @endif
  
  @if(session('error'))
  <div x-data="{ show: true }" 
       x-show="show" 
       x-init="setTimeout(() => show = false, 3000)"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 translate-y-0"
       x-transition:leave-end="opacity-0 -translate-y-2"
       class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-red-50 border border-error text-error px-6 rounded-btn toast flex items-center gap-2 shadow-card-hover"
       style="height: 44px; width: fit-content;">
    <i data-lucide="alert-circle" class="w-5 h-5"></i>
    {{ session('error') }}
  </div>
  @endif
  
  <!-- メインコンテンツ -->
  <main class="flex-1 w-full mx-auto py-6 px-4 md:py-10 md:px-10" style="max-width: 90rem;">
    @yield('content')
  </main>
  
  <!-- フッター -->
  <footer class="bg-surface border-t border-divider mt-auto flex-shrink-0">
    <div class="max-w-7xl mx-auto px-10 py-6">
      <p class="text-center text-text-subtle text-sm">
        © 2025 Insight-Box. All rights reserved.
      </p>
    </div>
  </footer>
  
  <script>
    // Lucide Icons初期化
    lucide.createIcons();
  </script>
  
  @yield('scripts')
</body>
</html>
