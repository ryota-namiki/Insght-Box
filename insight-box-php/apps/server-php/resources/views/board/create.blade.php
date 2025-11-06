@extends('layouts.app')

@section('title', 'ボード作成 - Insight-Box')

@section('content')
<div class="mb-6">
  <h1 class="text-[28px] font-semibold text-text-main leading-tight">新しいボードを作成</h1>
  <p class="mt-2 text-base text-text-subtle leading-relaxed">カードを整理するためのボードを作成します</p>
</div>

<div class="bg-surface rounded-card shadow-card p-6 border border-divider">
  <form action="{{ route('board.store') }}" method="POST">
    @csrf
    
    <div class="mb-6">
      <label for="name" class="block text-sm font-medium text-text-main mb-2">
        ボード名 <span class="text-error">*</span>
      </label>
      <input 
        type="text" 
        id="name" 
        name="name" 
        value="{{ old('name') }}"
        class="w-full px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary text-sm text-text-main"
        placeholder="例：プロジェクトA、営業リード、アイデア整理"
        required
        maxlength="255"
      >
      @error('name')
      <p class="mt-1 text-sm text-error">{{ $message }}</p>
      @enderror
    </div>
    
    <div class="mb-6">
      <label for="description" class="block text-sm font-medium text-text-main mb-2">
        説明（任意）
      </label>
      <textarea 
        id="description" 
        name="description" 
        rows="4"
        class="w-full px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary text-sm text-text-main leading-relaxed"
        placeholder="このボードの目的や用途を記載"
        maxlength="1000"
      >{{ old('description') }}</textarea>
      @error('description')
      <p class="mt-1 text-sm text-error">{{ $message }}</p>
      @enderror
      <p class="mt-1 text-[13px] text-text-subtle">最大1000文字</p>
    </div>
    
    <div class="flex items-center justify-between">
      <a href="{{ route('board.list') }}" class="flex items-center gap-2 text-text-subtle hover:text-text-main transition-fast">
        <i data-lucide="chevron-left" class="w-4 h-4"></i>
        キャンセル
      </a>
      <button 
        type="submit" 
        class="flex items-center justify-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary"
      >
        <i data-lucide="save" class="w-4 h-4"></i>
        保存する
      </button>
    </div>
  </form>
</div>

<script>
  // Lucide Icons初期化
  lucide.createIcons();
</script>
@endsection
