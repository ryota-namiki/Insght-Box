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
        <span class="material-icons text-6xl text-gray-300">inbox</span>
        <h3 class="mt-4 text-lg font-medium text-gray-900">カードがありません</h3>
        <p class="mt-2 text-gray-500">新しいカードを作成してみましょう</p>
        <a href="{{ route('cards.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            <span class="material-icons text-sm mr-2">add</span>
            カードを作成
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($cards as $card)
            <a href="{{ route('cards.show', $card['id']) }}" class="card bg-white rounded-lg shadow-md hover:shadow-xl overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-900 line-clamp-2">
                            {{ $card['title'] }}
                        </h3>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full {{ $card['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $card['status'] === 'published' ? '公開' : '下書き' }}
                        </span>
                    </div>
                    
                    @if($card['company'])
                        <p class="text-sm text-gray-600 mb-3">
                            <span class="material-icons text-sm align-middle">business</span>
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
                        <span class="flex items-center">
                            <span class="material-icons text-sm mr-1">schedule</span>
                            {{ \Carbon\Carbon::parse($card['createdAt'])->format('Y/m/d') }}
                        </span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection

