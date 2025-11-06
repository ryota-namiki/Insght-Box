@extends('layouts.app')

@section('title', 'ボード一覧 - Insight-Box')

@section('content')
<div class="mb-8 flex items-center justify-between">
  <div>
    <h1 class="text-[28px] font-semibold text-text-main leading-tight">ボード一覧</h1>
    <p class="mt-2 text-base text-text-subtle leading-relaxed">カードを整理するためのボードを管理できます</p>
  </div>
  <a href="{{ route('board.create') }}" class="flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary">
    <i data-lucide="plus" class="w-4 h-4"></i>
    新規ボード作成
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  @forelse($boards as $board)
  <div class="bg-surface rounded-card shadow-card transition-all duration-200 hover:bg-gray-100 card">
    <div class="p-6">
      <div class="flex items-start justify-between mb-4">
        <h2 class="text-lg font-medium text-text-main">{{ $board['name'] }}</h2>
        <div class="flex items-center gap-1">
          <a href="{{ route('board.edit', $board['id']) }}" class="text-primary hover:bg-white rounded transition-all p-1" title="編集">
            <i data-lucide="pencil" class="w-4 h-4"></i>
          </a>
          <form action="{{ route('board.destroy', $board['id']) }}" method="POST" 
                onsubmit="return confirm('このボードを削除してもよろしいですか？カードは削除されません。');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-error hover:bg-white rounded transition-all p-1" title="削除">
              <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
          </form>
        </div>
      </div>
      
      @if($board['description'])
      <p class="text-sm text-text-subtle mb-4 line-clamp-2 leading-relaxed">{{ $board['description'] }}</p>
      @endif
      
      <div class="flex items-center justify-end text-[13px] text-text-subtle mb-4">
        <span>{{ \Carbon\Carbon::parse($board['updated_at'] ?? $board['updatedAt'])->locale('ja')->diffForHumans() }}</span>
      </div>
      
      <a href="{{ route('board.show', $board['id']) }}" 
         class="flex items-center justify-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary w-full">
        ボードを開く
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
  @empty
  <div class="col-span-full">
    <div class="bg-surface rounded-card shadow-card p-12 text-center">
      <i data-lucide="layout-dashboard" class="w-16 h-16 text-divider mx-auto mb-4"></i>
      <h3 class="text-xl font-medium text-text-main mb-2">ボードがありません</h3>
      <p class="text-text-subtle mb-6 leading-relaxed">新しいボードを作成して、カードを整理しましょう</p>
      <a href="{{ route('board.create') }}" class="inline-flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        最初のボードを作成
      </a>
    </div>
  </div>
  @endforelse
</div>

<script>
  // Lucide Icons初期化
  lucide.createIcons();
</script>
@endsection
