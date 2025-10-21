@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
  <h1 class="text-3xl font-bold mb-6">お気に入り</h1>

  @if(count($cards) > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($cards as $card)
    <div class="relative bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
      <!-- お気に入りボタン -->
      <button 
        onclick="event.preventDefault(); toggleFavorite('{{ $card['id'] }}', this)" 
        class="absolute top-3 right-3 z-10 p-2 rounded-full hover:bg-gray-100 transition-colors"
        data-favorited="true"
        title="お気に入りから削除"
      >
        <span class="material-icons text-2xl text-red-500">favorite</span>
      </button>

      <a href="{{ route('cards.show', $card['id']) }}" class="block p-6 pt-12">
        <h3 class="text-xl font-semibold mb-2 hover:text-indigo-600">
          {{ $card['title'] }}
        </h3>

        @if($card['company'])
        <p class="text-gray-600 mb-2 flex items-center">
          <span class="material-icons text-sm mr-1">business</span>
          {{ $card['company'] }}
        </p>
        @endif

        @if(!empty($card['tags']))
        <div class="flex flex-wrap gap-2 mb-3">
          @foreach(array_slice($card['tags'], 0, 3) as $tag)
          <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-sm">
            {{ $tag['label'] ?? $tag['id'] ?? $tag }}
          </span>
          @endforeach
          @if(count($card['tags']) > 3)
          <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-sm">
            +{{ count($card['tags']) - 3 }}
          </span>
          @endif
        </div>
        @endif

        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-gray-500">
          <span class="flex items-center">
            <span class="material-icons text-sm mr-1">schedule</span>
            お気に入り登録: {{ \Carbon\Carbon::parse($card['favoritedAt'])->format('Y/m/d') }}
          </span>
        </div>
      </a>
    </div>
    @endforeach
  </div>
  @else
  <div class="text-center py-12">
    <p class="text-gray-500 text-lg mb-4">お気に入りのカードはありません</p>
    <a href="{{ route('cards.index') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
      カード一覧へ
    </a>
  </div>
  @endif
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

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
    
    if (data.success && !data.isFavorited) {
      // お気に入りから削除された場合、カードを非表示にする
      button.closest('.relative').style.opacity = '0';
      setTimeout(() => {
        location.reload();
      }, 300);
    }
  } catch (error) {
    console.error('お気に入り操作エラー:', error);
    alert('お気に入り操作に失敗しました');
  }
}
</script>
@endsection

