@extends('layouts.app')

@section('title', 'カード編集 - Insight-Box')

@section('content')
<div class="mx-auto">
    <div class="mb-6">
        <a href="{{ route('cards.show', $card['id']) }}" class="flex items-center text-gray-600 hover:text-gray-900">
            <span class="material-icons mr-1">arrow_back</span>
            詳細に戻る
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">カードを編集</h1>
        
        <form action="{{ route('cards.update', $card['id']) }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- タイトル -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    タイトル <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    required
                    value="{{ old('title', $card['summary']['title']) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>
            
            <!-- 会社名 -->
            <div class="mb-6">
                <label for="companyName" class="block text-sm font-medium text-gray-700 mb-2">
                    会社名
                </label>
                <input 
                    type="text" 
                    id="companyName" 
                    name="companyName" 
                    value="{{ old('companyName', $card['summary']['company']) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>
            
            <!-- メモ -->
            <div class="mb-6">
                <label for="memo" class="block text-sm font-medium text-gray-700 mb-2">
                    メモ
                </label>
                <textarea 
                    id="memo" 
                    name="memo" 
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                >{{ old('memo', $card['detail']['memo']) }}</textarea>
            </div>
            
            <!-- イベントID -->
            <div class="mb-6">
                <label for="eventId" class="block text-sm font-medium text-gray-700 mb-2">
                    イベント <span class="text-red-500">*</span>
                </label>
                <select 
                    id="eventId" 
                    name="eventId" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
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
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    タグ
                </label>
                <div class="flex gap-2 mb-2">
                    <input 
                        type="text" 
                        x-model="tagInput"
                        @keydown.enter.prevent="if(tagInput.trim()) { tags.push({id: tagInput.trim(), label: tagInput.trim()}); tagInput = ''; }"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="タグを入力してEnter"
                    >
                    <button 
                        type="button"
                        @click="if(tagInput.trim()) { tags.push({id: tagInput.trim(), label: tagInput.trim()}); tagInput = ''; }"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                    >
                        追加
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(tag, index) in tags" :key="index">
                        <span class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">
                            <span x-text="tag.label"></span>
                            <button 
                                type="button"
                                @click="tags.splice(index, 1)"
                                class="ml-2 text-indigo-600 hover:text-indigo-800"
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
            <div class="flex justify-end gap-3">
                <a href="{{ route('cards.show', $card['id']) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                    キャンセル
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                >
                    更新
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

