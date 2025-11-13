@extends('layouts.app')

@section('title', $card['summary']['title'] . ' - Insight-Box')

@section('content')
<div class="mx-auto">
  <!-- ヘッダー -->
  <div class="mb-6 flex items-center justify-between">
      <a href="{{ route('cards.index') }}" class="flex items-center gap-2 text-gray-600 hover:text-gray-900">
      <i data-lucide="chevron-left" class="w-4 h-4"></i>
      一覧に戻る
      </a>
      <div class="flex items-center space-x-2">
      <!-- お気に入りボタン -->
      <button 
          id="favorite-btn"
          onclick="toggleFavorite('{{ $card['id'] }}')" 
          class="flex items-center gap-2 h-12 px-4 {{ $isFavorited ? 'bg-error hover:brightness-95' : 'bg-gray-200 hover:bg-gray-300' }} text-white rounded-btn text-sm font-medium transition-fast"
          data-favorited="{{ $isFavorited ? 'true' : 'false' }}"
          title="{{ $isFavorited ? 'お気に入りから削除' : 'お気に入りに追加' }}"
      >
          <i data-lucide="heart" id="favorite-icon" class="w-4 h-4 {{ $isFavorited ? 'fill-current' : '' }}"></i>
          <span id="favorite-text">{{ $isFavorited ? 'お気に入り済み' : 'お気に入り' }}</span>
      </button>

      <a href="{{ route('cards.edit', $card['id']) }}" class="flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary">
          <i data-lucide="pencil" class="w-4 h-4"></i>
          編集
      </a>
      <button 
          onclick="if(confirm('このカードを削除しますか？')) { document.getElementById('delete-form').submit(); }"
          class="flex items-center gap-2 h-12 px-4 bg-error text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast"
      >
          <i data-lucide="trash-2" class="w-4 h-4"></i>
          削除
      </button>
      <form id="delete-form" action="{{ route('cards.destroy', $card['id']) }}" method="POST" class="hidden">
          @csrf
          @method('DELETE')
      </form>
      </div>
  </div>
  
  <!-- カード詳細 -->
  <div class="bg-white rounded-lg shadow-lg">
      <!-- ヘッダー部分 -->
      <div class="px-8 py-6 border-b border-gray-200">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">
        {{ $card['summary']['title'] }}
      </h1>
      
      @if($card['summary']['company'])
          <p class="text-lg text-gray-700 mb-4 flex items-center gap-2">
        <i data-lucide="building-2" class="w-5 h-5"></i>
        {{ $card['summary']['company'] }}
          </p>
      @endif
      
      @if(!empty($card['summary']['tags']))
          <div class="flex flex-wrap gap-2 mb-4">
        @foreach($card['summary']['tags'] as $tag)
            <span class="px-3 py-1 text-sm bg-indigo-100 text-indigo-800 rounded-full">
        {{ $tag['label'] ?? $tag['id'] }}
            </span>
        @endforeach
          </div>
      @endif
      
      <div class="flex items-center space-x-6 text-sm text-gray-500">
          <span class="flex items-center gap-1">
        <i data-lucide="calendar" class="w-4 h-4"></i>
        作成: {{ \Carbon\Carbon::parse($card['summary']['createdAt'])->format('Y年m月d日 H:i') }}
          </span>
          <span class="flex items-center gap-1">
        <i data-lucide="clock" class="w-4 h-4"></i>
        更新: {{ \Carbon\Carbon::parse($card['summary']['updatedAt'])->format('Y年m月d日 H:i') }}
          </span>
      </div>
      </div>
      
      <!-- Webクリップ -->
      @if($card['detail']['webclipUrl'])
      <div class="px-8 py-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i data-lucide="globe" class="w-5 h-5"></i>
        Webクリップ
          </h2>
          <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-5">
        <div class="mb-4">
            <p class="text-xs text-gray-500 mb-2">🔗 参照URL</p>
            <a href="{{ $card['detail']['webclipUrl'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 hover:underline break-all text-sm">
        {{ $card['detail']['webclipUrl'] }}
            </a>
        </div>
        
        @if($card['detail']['webclipSummary'])
            <div class="mt-4 pt-4 border-t border-blue-200">
        <p class="text-xs text-gray-500 mb-2">📝 AI生成要約（300文字）</p>
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <p class="text-gray-800 leading-relaxed">{{ $card['detail']['webclipSummary'] }}</p>
        </div>
            </div>
        @endif
          </div>
      </div>
      @endif
      
      <!-- アップロードファイルと抽出テキスト（横並び） -->
      @if($card['detail']['documentId'] || $card['detail']['text'])
      <div class="px-8 py-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <i data-lucide="file-text" class="w-5 h-5"></i>
        アップロードファイル
          </h2>
          
          <!-- 横並びレイアウト（flex） -->
          <div class="flex flex-col md:flex-row gap-6 items-start">
        <!-- 左: ファイルイメージ -->
        @if($card['detail']['documentId'])
            <div class="bg-gray-50 rounded-lg p-4 w-full {{ ($card['detail']['documentId'] && $card['detail']['text']) ? 'md:w-1/2' : 'md:w-full' }}">
        <div class="flex items-center gap-3 mb-3">
            <i data-lucide="file" class="w-6 h-6 text-primary"></i>
            <div>
                <p class="font-medium text-gray-900 text-sm">ドキュメントID</p>
                <p class="text-xs text-gray-500">{{ substr($card['detail']['documentId'], 0, 20) }}...</p>
            </div>
        </div>
        
        <!-- 画像プレビュー（APIから取得） -->
        <div class="mt-3">
            <img 
                src="{{ url('/api/v1/documents/' . $card['detail']['documentId'] . '/image') }}" 
                alt="アップロードファイル"
                class="w-full h-auto rounded-lg shadow-md"
                id="preview-image"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
            >
            <div style="display:none;" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-800 flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3"></i>
          画像プレビューは利用できません
                </p>
            </div>
        </div>
            </div>
        @endif
        
        <!-- 右: 抽出されたテキスト -->
        @if($card['detail']['text'])
            <div class="bg-gray-50 rounded-lg p-4 flex flex-col w-full {{ ($card['detail']['documentId'] && $card['detail']['text']) ? 'md:w-1/2' : 'md:w-full' }}">
        <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            抽出されたテキスト
        </h3>
        <div class="bg-white rounded p-3 overflow-y-auto flex-1 min-h-0" id="text-container" style="max-height: 600px;">
            <p class="text-gray-700 whitespace-pre-wrap text-sm leading-relaxed m-0">{{ $card['detail']['text'] }}</p>
        </div>
            </div>
        @endif
          </div>
      </div>
      @endif
      
      <!-- カメラ画像 -->
      @if($card['detail']['cameraImage'])
      <div class="px-8 py-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i data-lucide="camera" class="w-5 h-5"></i>
        カメラ撮影画像
          </h2>
          <img 
        src="{{ $card['detail']['cameraImage'] }}" 
        alt="撮影画像"
        class="rounded-lg shadow-md"
        style="max-width: 50%; height: auto;"
          >
      </div>
      @endif
      
      <!-- メモ -->
      @if($card['detail']['memo'])
      <div class="px-8 py-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i data-lucide="file-edit" class="w-5 h-5"></i>
        メモ
          </h2>
          <div class="prose max-w-none">
        <p class="text-gray-700 whitespace-pre-wrap">{{ $card['detail']['memo'] }}</p>
          </div>
      </div>
      @endif
  </div>
</div>
@endsection

@section('scripts')
<script>
// CSRFトークン
const csrfToken = '{{ csrf_token() }}';

// お気に入りトグル
async function toggleFavorite(cardId) {
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
    const button = document.getElementById('favorite-btn');
    const text = document.getElementById('favorite-text');
    const isFavorited = data.isFavorited;

    // 要素が存在する場合のみ更新
    if (button && text) {
      // ボタンのスタイルを更新
      if (isFavorited) {
        button.className = 'flex items-center gap-2 h-12 px-4 bg-error hover:brightness-95 text-white rounded-btn text-sm font-medium transition-fast';
        text.textContent = 'お気に入り済み';
        button.setAttribute('title', 'お気に入りから削除');
      } else {
        button.className = 'flex items-center gap-2 h-12 px-4 bg-gray-200 hover:bg-gray-300 text-white rounded-btn text-sm font-medium transition-fast';
        text.textContent = 'お気に入り';
        button.setAttribute('title', 'お気に入りに追加');
      }
      button.setAttribute('data-favorited', isFavorited ? 'true' : 'false');
      
      // ヘルパー関数：ハートアイコンを更新
      function updateHeartIcon(svgElement, favorited) {
        if (!svgElement) return;
        
        // 全てのパス要素を取得
        const paths = svgElement.querySelectorAll('path');
        if (paths.length === 0) return;
        
        if (favorited) {
          // お気に入り状態：白色で塗りつぶし（ボタン背景が赤なので）
          svgElement.style.color = '#FFFFFF';
          svgElement.style.fill = '#FFFFFF';
          paths.forEach(path => {
            path.setAttribute('fill', '#FFFFFF');
            path.setAttribute('stroke', '#FFFFFF');
          });
        } else {
          // 非お気に入り状態：白色で塗りつぶしなし（ボタン背景がグレーなので）
          svgElement.style.color = '#FFFFFF';
          svgElement.style.fill = 'none';
          paths.forEach(path => {
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', '#FFFFFF');
          });
        }
      }
      
      // SVG要素を直接探して更新（Lucideで変換済みの場合）
      let svg = button.querySelector('svg.lucide-heart');
      
      if (!svg) {
        // SVGが見つからない場合、<i>タグを探す
        const iconTag = button.querySelector('i[data-lucide="heart"]') || document.getElementById('favorite-icon');
        if (iconTag && typeof lucide !== 'undefined') {
          // Lucideアイコンを初期化
          lucide.createIcons();
          // 少し待ってからSVGを取得
          setTimeout(() => {
            svg = button.querySelector('svg.lucide-heart');
            updateHeartIcon(svg, isFavorited);
          }, 50);
        }
      } else {
        // SVGが見つかった場合、即座に更新
        updateHeartIcon(svg, isFavorited);
      }
    }

  } catch (error) {
    console.error('お気に入り操作エラー:', error);
    alert('お気に入り操作に失敗しました');
  }
}

// 画像の高さに合わせてテキストエリアの高さを調整
function adjustTextHeight() {
  const previewImage = document.getElementById('preview-image');
  const textContainer = document.getElementById('text-container');
  
  if (previewImage && textContainer && previewImage.style.display !== 'none') {
      // 画像の読み込みが完了してから高さを取得
      if (previewImage.complete) {
      const imageHeight = previewImage.offsetHeight;
      if (imageHeight > 0) {
          textContainer.style.maxHeight = imageHeight + 'px';
      }
      } else {
      previewImage.addEventListener('load', function() {
          const imageHeight = previewImage.offsetHeight;
          if (imageHeight > 0) {
        textContainer.style.maxHeight = imageHeight + 'px';
          }
      });
      }
  }
}

// ページ読み込み時とウィンドウリサイズ時に調整
window.addEventListener('load', adjustTextHeight);
window.addEventListener('resize', adjustTextHeight);
</script>
@endsection

