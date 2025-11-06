@extends('layouts.app')

@section('title', 'カード編集 - Insight-Box')

@section('content')
<div class="mb-6">
  <h1 class="text-3xl font-bold text-text-main">カードを編集</h1>
  <p class="mt-2 text-text-subtle">カード情報を更新します</p>
</div>

<div class="bg-surface rounded-card shadow-card p-6">
  <form action="{{ route('cards.update', $card['id']) }}" method="POST">
    @csrf
    @method('PUT')
    
    <!-- タイトル -->
    <div class="mb-6">
      <label for="title" class="block text-sm font-medium text-text-main mb-2">
        タイトル <span class="text-error">*</span>
      </label>
      <input 
        type="text" 
        id="title" 
        name="title" 
        required
        value="{{ old('title', $card['summary']['title']) }}"
        class="w-full px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary"
      >
    </div>
    
    <!-- 会社名 -->
    <div class="mb-6">
      <label for="companyName" class="block text-sm font-medium text-text-main mb-2">
        会社名
      </label>
      <input 
        type="text" 
        id="companyName" 
        name="companyName" 
        value="{{ old('companyName', $card['summary']['company']) }}"
        class="w-full px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary"
      >
    </div>
    
    <!-- メモ -->
    <div class="mb-6">
      <label for="memo" class="block text-sm font-medium text-text-main mb-2">
        メモ
      </label>
      <textarea 
        id="memo" 
        name="memo" 
        rows="6"
        class="w-full px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary"
      >{{ old('memo', $card['detail']['memo']) }}</textarea>
    </div>
    
    <!-- イベントID -->
    <div class="mb-6">
      <label for="eventId" class="block text-sm font-medium text-text-main mb-2">
        イベント <span class="text-error">*</span>
      </label>
      <select 
        id="eventId" 
        name="eventId" 
        required
        class="w-full px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary"
      >
        @foreach($events as $event)
        <option value="{{ $event['id'] }}" {{ old('eventId', $card['summary']['eventId']) == $event['id'] ? 'selected' : '' }}>
          {{ $event['name'] }}
        </option>
        @endforeach
      </select>
    </div>
    
    <!-- タグ -->
    <div class="mb-6" x-data="{ tagInput: '', tags: @js($card['summary']['tags'] ?? []) }">
      <label class="block text-sm font-medium text-text-main mb-2">
        タグ
      </label>
      <div class="flex gap-2 mb-2">
        <input 
          type="text" 
          x-model="tagInput"
          @keydown.enter.prevent="if(tagInput.trim()) { tags.push({id: tagInput.trim(), label: tagInput.trim()}); tagInput = ''; }"
          class="flex-1 px-3 py-2 border border-divider rounded-btn focus:outline-none focus:ring-2 focus:ring-primary"
          placeholder="タグを入力してEnter"
        >
        <button 
          type="button"
          @click="if(tagInput.trim()) { tags.push({id: tagInput.trim(), label: tagInput.trim()}); tagInput = ''; }"
          class="px-4 py-2 bg-gray-200 text-gray-700 rounded-btn hover:bg-gray-300 transition-fast"
        >
          追加
        </button>
      </div>
      <div class="flex flex-wrap gap-2">
        <template x-for="(tag, index) in tags" :key="index">
          <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-primary rounded-full text-sm">
            <span x-text="tag.label"></span>
            <button 
              type="button"
              @click="tags.splice(index, 1)"
              class="ml-2 text-primary hover:text-primary-dark"
            >
              ×
            </button>
            <input type="hidden" :name="'tags[' + index + '][id]'" :value="tag.id">
            <input type="hidden" :name="'tags[' + index + '][label]'" :value="tag.label">
          </span>
        </template>
      </div>
    </div>
    
    <!-- ボタン -->
    <div class="flex items-center justify-between">
      <a href="{{ route('cards.show', $card['id']) }}" class="flex items-center gap-2 text-text-subtle hover:text-text-main transition-fast">
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
@endsection
