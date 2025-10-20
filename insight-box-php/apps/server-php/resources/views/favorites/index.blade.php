@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
  <h1 class="text-3xl font-bold mb-6">お気に入り</h1>

  @if(count($cards) > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($cards as $card)
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <h3 class="text-xl font-semibold mb-2">
        <a href="{{ route('cards.show', $card['id']) }}" class="hover:text-indigo-600">
          {{ $card['title'] }}
        </a>
      </h3>

      @if($card['company'])
      <p class="text-gray-600 mb-2">{{ $card['company'] }}</p>
      @endif

      @if(!empty($card['tags']))
      <div class="flex flex-wrap gap-2 mb-3">
        @foreach($card['tags'] as $tag)
        <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-sm">
          {{ $tag }}
        </span>
        @endforeach
      </div>
      @endif

      <div class="flex justify-between items-center mt-4 text-sm text-gray-500">
        <span>お気に入り登録: {{ \Carbon\Carbon::parse($card['favoritedAt'])->format('Y/m/d') }}</span>
        <button onclick="toggleFavorite('{{ $card['id'] }}')" class="text-red-500 hover:text-red-700">
          <span class="material-icons">favorite</span>
        </button>
      </div>
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
async function toggleFavorite(cardId) {
  try {
    const response = await fetch('{{ route("favorites.toggle") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ card_id: cardId })
    });

    const data = await response.json();

    if (data.success) {
      // ページをリロードして更新
      location.reload();
    }
  } catch (error) {
    console.error('お気に入り切り替えエラー:', error);
  }
}
</script>
@endsection

