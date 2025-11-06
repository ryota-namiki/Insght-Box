@extends('layouts.app')

@section('title', 'カード一覧 - Insight-Box')

@section('content')
<div class="mb-6">
  <h1 class="text-3xl font-bold text-gray-900">カード一覧</h1>
  <p class="mt-2 text-gray-600">登録されたカードの一覧です</p>
</div>

<!-- 検索・フィルター -->
<div class="mb-6 bg-white rounded-lg shadow p-4" x-data="{ search: '', eventFilter: '' }">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">検索</label>
      <input 
          type="text" 
          x-model="search" 
          placeholder="タイトルまたは会社名で検索..."
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
      >
      </div>
      <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">イベント</label>
      <select 
          x-model="eventFilter"
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
      >
          <option value="">すべて</option>
          @foreach($events as $event)
        <option value="{{ $event['id'] }}">{{ $event['name'] }}</option>
          @endforeach
      </select>
      </div>
      <div class="flex items-end">
      <button 
          @click="search = ''; eventFilter = ''"
          class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
      >
          クリア
      </button>
      </div>
  </div>
</div>

<!-- カードグリッド -->
@if(empty($cards))
  <div class="bg-white rounded-lg shadow p-12 text-center">
      <i data-lucide="inbox" class="w-16 h-16 text-gray-300 mx-auto"></i>
      <h3 class="mt-4 text-lg font-medium text-gray-900">カードがありません</h3>
      <p class="mt-2 text-gray-500">新しいカードを作成してみましょう</p>
      <a href="{{ route('cards.create') }}" class="mt-4 inline-flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast">
      <i data-lucide="plus" class="w-4 h-4"></i>
      カードを作成
      </a>
  </div>
@else
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($cards as $card)
      <div class="relative card bg-white rounded-lg shadow-md overflow-hidden transition-all duration-200 hover:bg-gray-100">
          <!-- お気に入りボタン -->
          <button 
        onclick="event.preventDefault(); toggleFavorite('{{ $card['id'] }}', this)" 
        class="absolute top-3 right-3 z-10 p-2 rounded-full hover:bg-white transition-all"
        data-favorited="{{ $card['isFavorited'] ? 'true' : 'false' }}"
        title="{{ $card['isFavorited'] ? 'お気に入りから削除' : 'お気に入りに追加' }}"
          >
        <i data-lucide="heart" class="w-6 h-6 {{ $card['isFavorited'] ? 'text-error fill-error' : 'text-gray-300' }}"></i>
          </button>

          <a href="{{ route('cards.show', $card['id']) }}" class="block p-6 pt-12">
        <h3 class="text-lg font-semibold text-gray-900 line-clamp-2 mb-3">
          {{ $card['title'] }}
        </h3>
        
        @if($card['company'])
            <p class="text-sm text-gray-600 mb-3 flex items-center gap-1">
        <i data-lucide="building-2" class="w-4 h-4"></i>
        {{ $card['company'] }}
            </p>
        @endif
        
        @if(!empty($card['tags']))
            <div class="flex flex-wrap gap-2 mb-3">
        @foreach(array_slice($card['tags'], 0, 3) as $tag)
            <span class="px-2 py-1 text-xs bg-indigo-100 text-indigo-800 rounded">
                {{ $tag['label'] ?? $tag['id'] }}
            </span>
        @endforeach
        @if(count($card['tags']) > 3)
            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                +{{ count($card['tags']) - 3 }}
            </span>
        @endif
            </div>
        @endif
        
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-gray-500">
            <span class="flex items-center gap-1">
        <i data-lucide="calendar" class="w-4 h-4"></i>
        {{ \Carbon\Carbon::parse($card['createdAt'])->format('Y/m/d') }}
            </span>
        </div>
          </a>
      </div>
      @endforeach
  </div>
@endif
@endsection

@section('scripts')
<script>
// CSRFトークン
const csrfToken = '{{ csrf_token() }}';

// お気に入りトグル
async function toggleFavorite(cardId, button) {
  try {
    const response = await fetch('{{ route("favorites.toggle") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ card_id: cardId })
    });

    if (!response.ok) throw new Error('Failed to toggle favorite');

    const data = await response.json();
    const isFavorited = data.isFavorited;
    
    // ボタンの属性を更新
    button.setAttribute('data-favorited', isFavorited ? 'true' : 'false');
    button.setAttribute('title', isFavorited ? 'お気に入りから削除' : 'お気に入りに追加');
    
    // SVG要素を直接探して更新（Lucideで変換済みの場合）
    let icon = button.querySelector('svg.lucide-heart');
    
    if (icon) {
      // SVGが既に存在する場合、クラスとスタイルを更新
      if (isFavorited) {
        icon.className = 'lucide lucide-heart w-6 h-6 text-error';
        icon.style.fill = 'currentColor';
      } else {
        icon.className = 'lucide lucide-heart w-6 h-6 text-gray-300';
        icon.style.fill = 'none';
      }
    } else {
      // SVGがない場合、<i>タグを探して再作成
      const oldIcon = button.querySelector('i[data-lucide="heart"]');
      if (oldIcon) {
        const newIcon = document.createElement('i');
        newIcon.setAttribute('data-lucide', 'heart');
        newIcon.className = 'w-6 h-6 text-error';
        oldIcon.replaceWith(newIcon);
        
        // Lucideアイコンを初期化
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
          
          // 初期化後、SVGを取得してfillを設定
          setTimeout(() => {
            const svg = button.querySelector('svg.lucide-heart');
            if (svg && isFavorited) {
              svg.style.fill = 'currentColor';
            } else if (svg) {
              svg.style.fill = 'none';
            }
          }, 10);
        }
      }
    }

  } catch (error) {
    console.error('お気に入り操作エラー:', error);
    alert('お気に入り操作に失敗しました');
  }
}
</script>
@endsection

